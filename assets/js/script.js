// ===== CONFIGURATION =====
const CONFIG = {
  API_BASE_URL: "api/",
  MAP_CENTER: [-18.8506, -41.9492], // Governador Valadares
  MAP_ZOOM: 13,
  ITEMS_PER_PAGE: 12,
  DEBOUNCE_DELAY: 300,
  ANIMATION_DURATION: 300,
  NOTIFICATION_DURATION: 5000,
}

// ===== APPLICATION STATE =====
const AppState = {
  establishments: [],
  filteredEstablishments: [],
  featuredEstablishments: [],
  promotions: [],
  events: [],
  currentPage: 1,
  isLoading: false,
  searchQuery: "",
  activeFilters: {
    category: "",
    neighborhood: "",
    price: "",
    rating: "",
    openNow: false,
    minRating: 0,
  },
  sortBy: "relevance",
  viewMode: "grid",
  map: null,
  markers: [],
  userLocation: null,
  user: null,
  isInitialized: false,
}

// ===== UTILITY FUNCTIONS =====
const Utils = {
  // Debounce function for search
  debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout)
        func(...args)
      }
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
    }
  },

  // Format rating number
  formatRating(rating) {
    return Number.parseFloat(rating || 0).toFixed(1)
  },

  // Format price range
  formatPrice(priceRange) {
    return "$".repeat(Number.parseInt(priceRange) || 1)
  },

  // Format distance
  formatDistance(distance) {
    if (distance < 1000) {
      return `${Math.round(distance)}m`
    }
    return `${(distance / 1000).toFixed(1)}km`
  },

  // Generate star rating HTML
  generateStars(rating) {
    const fullStars = Math.floor(rating)
    const hasHalfStar = rating % 1 >= 0.5
    let starsHtml = ""

    for (let i = 0; i < 5; i++) {
      if (i < fullStars) {
        starsHtml += '<i class="fas fa-star"></i>'
      } else if (i === fullStars && hasHalfStar) {
        starsHtml += '<i class="fas fa-star-half-alt"></i>'
      } else {
        starsHtml += '<i class="far fa-star"></i>'
      }
    }

    return starsHtml
  },

  // Show notification
  showNotification(message, type = "info", duration = CONFIG.NOTIFICATION_DURATION) {
    const container = document.getElementById("notificationContainer")
    if (!container) return

    const notification = document.createElement("div")
    notification.className = `notification ${type}`

    const icons = {
      success: "fas fa-check-circle",
      error: "fas fa-exclamation-circle",
      warning: "fas fa-exclamation-triangle",
      info: "fas fa-info-circle",
    }

    notification.innerHTML = `
      <div class="notification-header">
        <div class="notification-title">
          <i class="${icons[type]}"></i>
          ${type.charAt(0).toUpperCase() + type.slice(1)}
        </div>
        <button class="notification-close">&times;</button>
      </div>
      <div class="notification-message">${message}</div>
    `

    container.appendChild(notification)

    // Show notification
    setTimeout(() => notification.classList.add("show"), 100)

    // Auto remove
    const autoRemove = setTimeout(() => {
      notification.classList.remove("show")
      setTimeout(() => {
        if (container.contains(notification)) {
          container.removeChild(notification)
        }
      }, CONFIG.ANIMATION_DURATION)
    }, duration)

    // Remove on click
    notification.querySelector(".notification-close").addEventListener("click", () => {
      clearTimeout(autoRemove)
      notification.classList.remove("show")
      setTimeout(() => {
        if (container.contains(notification)) {
          container.removeChild(notification)
        }
      }, CONFIG.ANIMATION_DURATION)
    })
  },

  // Animate element
  animateElement(element, animation = "fadeIn") {
    if (!element) return
    element.classList.add(animation)
    setTimeout(() => element.classList.remove(animation), 600)
  },

  // Smooth scroll to element
  scrollToElement(element, offset = 80) {
    if (!element) return
    const elementPosition = element.offsetTop - offset
    window.scrollTo({
      top: elementPosition,
      behavior: "smooth",
    })
  },

  // Format date
  formatDate(dateString) {
    const date = new Date(dateString)
    return date.toLocaleDateString("pt-BR")
  },

  // Truncate text
  truncateText(text, maxLength = 100) {
    if (!text || text.length <= maxLength) return text
    return text.substring(0, maxLength) + "..."
  },

  // Generate slug from string
  generateSlug(text) {
    return text
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9\s-]/g, "")
      .replace(/\s+/g, "-")
      .replace(/-+/g, "-")
      .trim("-")
  },

  // Safe DOM query
  safeQuery(selector) {
    try {
      return document.querySelector(selector)
    } catch (error) {
      console.warn(`Invalid selector: ${selector}`)
      return null
    }
  },

  // Safe DOM query all
  safeQueryAll(selector) {
    try {
      return document.querySelectorAll(selector)
    } catch (error) {
      console.warn(`Invalid selector: ${selector}`)
      return []
    }
  },
}

// ===== API SERVICE =====
const API = {
  async request(endpoint, options = {}) {
    try {
      AppState.isLoading = true
      const response = await fetch(CONFIG.API_BASE_URL + endpoint, {
        headers: {
          "Content-Type": "application/json",
          ...options.headers,
        },
        ...options,
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error("API Error:", error)
      // Don't show error notification for initial load
      if (AppState.isInitialized) {
        Utils.showNotification("Erro ao conectar com o servidor", "error")
      }
      throw error
    } finally {
      AppState.isLoading = false
    }
  },

  // Get all establishments
  async getEstablishments(params = {}) {
    const queryString = new URLSearchParams(params).toString()
    return this.request(`establishments.php?${queryString}`)
  },

  // Get featured establishments
  async getFeaturedEstablishments() {
    return this.request("get-featured-establishments.php")
  },

  // Search establishments
  async searchEstablishments(query, filters = {}) {
    return this.request("search.php", {
      method: "POST",
      body: JSON.stringify({ query, filters }),
    })
  },

  // Get establishment details
  async getEstablishmentDetails(id) {
    return this.request(`establishments.php?id=${id}`)
  },

  // Get promotions
  async getPromotions(establishmentId = null) {
    const params = establishmentId ? `?establishment_id=${establishmentId}` : ""
    return this.request(`get-promotions.php${params}`)
  },

  // Get events
  async getEvents(establishmentId = null) {
    const params = establishmentId ? `?establishment_id=${establishmentId}` : ""
    return this.request(`get-events.php${params}`)
  },

  // Add review
  async addReview(establishmentId, rating, comment) {
    return this.request("add-review.php", {
      method: "POST",
      body: JSON.stringify({
        establishment_id: establishmentId,
        rating,
        comment,
      }),
    })
  },

  // Toggle favorite
  async toggleFavorite(establishmentId) {
    return this.request("toggle-favorite.php", {
      method: "POST",
      body: JSON.stringify({
        establishment_id: establishmentId,
      }),
    })
  },

  // Get user favorites
  async getUserFavorites() {
    return this.request("get-user-favorites.php")
  },

  // Increment view count
  async incrementViewCount(establishmentId) {
    return this.request("increment-view-count.php", {
      method: "POST",
      body: JSON.stringify({
        establishment_id: establishmentId,
      }),
    })
  },
}

// ===== ESTABLISHMENT MANAGER =====
const EstablishmentManager = {
  async loadEstablishments() {
    try {
      const data = await API.getEstablishments()
      AppState.establishments = data.establishments || this.getMockData()
      AppState.filteredEstablishments = [...AppState.establishments]
      this.renderEstablishments()
      this.updateStats()
    } catch (error) {
      console.error("Erro ao carregar estabelecimentos:", error)
      AppState.establishments = this.getMockData()
      AppState.filteredEstablishments = [...AppState.establishments]
      this.renderEstablishments()
      this.updateStats()
    }
  },

  async loadFeaturedEstablishments() {
    try {
      const data = await API.getFeaturedEstablishments()
      AppState.featuredEstablishments = data.establishments || this.getMockData().slice(0, 6)
      this.renderFeaturedEstablishments()
    } catch (error) {
      console.error("Erro ao carregar estabelecimentos em destaque:", error)
      AppState.featuredEstablishments = this.getMockData().slice(0, 6)
      this.renderFeaturedEstablishments()
    }
  },

  getMockData() {
    return [
      {
        id: 1,
        name: "Restaurante Mineiro Tradicional",
        category: "restaurante",
        description:
          "Autêntica culinária mineira com pratos tradicionais e ambiente acolhedor no coração de Governador Valadares.",
        image_url: "public/mineiro.jpg",
        rating: 4.8,
        avg_rating: 4.8,
        total_reviews: 127,
        price_range: 2,
        neighborhood: "centro",
        address: "Rua Marechal Floriano, 123 - Centro",
        phone: "(33) 3271-1234",
        latitude: -18.8506,
        longitude: -41.9492,
        opening_hours: "11:00-22:00",
        is_open: true,
        features: ["Estacionamento", "Wi-Fi", "Ar Condicionado"],
        featured: true,
      },
      {
        id: 2,
        name: "Pizzaria Panorâmica",
        category: "restaurante",
        description: "Pizzas artesanais com vista panorâmica da cidade. Ambiente romântico e familiar.",
        image_url: "public/pizzaria.jpg",
        rating: 4.6,
        avg_rating: 4.6,
        total_reviews: 89,
        price_range: 2,
        neighborhood: "ibituruna",
        address: "Av. Minas Gerais, 456 - Ibituruna",
        phone: "(33) 3271-5678",
        latitude: -18.8456,
        longitude: -41.9442,
        opening_hours: "18:00-23:00",
        is_open: true,
        features: ["Vista Panorâmica", "Delivery", "Música Ao Vivo"],
        featured: true,
      },
      {
        id: 3,
        name: "Bar Música Ao Vivo",
        category: "bar",
        description: "O melhor da música ao vivo em GV. Ambiente descontraído com shows todas as sextas e sábados.",
        image_url: "public/cultural.jpeg",
        rating: 4.7,
        avg_rating: 4.7,
        total_reviews: 156,
        price_range: 2,
        neighborhood: "centro",
        address: "Rua Coronel Teodolino, 789 - Centro",
        phone: "(33) 3271-9012",
        latitude: -18.8516,
        longitude: -41.9502,
        opening_hours: "17:00-02:00",
        is_open: true,
        features: ["Música Ao Vivo", "Happy Hour", "Área Externa"],
        featured: true,
      },
      {
        id: 4,
        name: "Café Italiano Aconchegante",
        category: "cafe",
        description: "Café artesanal com grãos especiais e ambiente acolhedor. Perfeito para trabalhar ou relaxar.",
        image_url: "public/cultural.jpeg",
        rating: 4.5,
        avg_rating: 4.5,
        total_reviews: 73,
        price_range: 1,
        neighborhood: "santa-terezinha",
        address: "Rua Santos Dumont, 321 - Santa Terezinha",
        phone: "(33) 3271-3456",
        latitude: -18.8476,
        longitude: -41.9462,
        opening_hours: "07:00-19:00",
        is_open: true,
        features: ["Wi-Fi", "Café Especial", "Ambiente Silencioso"],
        featured: false,
      },
      {
        id: 5,
        name: "Casa de Shows Municipal",
        category: "cultura",
        description:
          "Teatro municipal com programação cultural diversificada. Shows, peças teatrais e eventos culturais.",
        image_url: "public/cultural.jpeg",
        rating: 4.9,
        avg_rating: 4.9,
        total_reviews: 234,
        price_range: 2,
        neighborhood: "centro",
        address: "Praça dos Pioneiros, 100 - Centro",
        phone: "(33) 3271-7890",
        latitude: -18.8496,
        longitude: -41.9482,
        opening_hours: "19:00-23:00",
        is_open: false,
        features: ["Ar Condicionado", "Estacionamento", "Acessibilidade"],
        featured: false,
      },
      {
        id: 6,
        name: "Balada Eletrônica GV",
        category: "bar",
        description: "A melhor música eletrônica da cidade. DJs renomados e pista de dança moderna.",
        image_url: "public/cultural.jpeg",
        rating: 4.4,
        avg_rating: 4.4,
        total_reviews: 198,
        price_range: 3,
        neighborhood: "grã-duquesa",
        address: "Av. Brasil, 567 - Grã Duquesa",
        phone: "(33) 3271-2468",
        latitude: -18.8526,
        longitude: -41.9512,
        opening_hours: "22:00-05:00",
        is_open: false,
        features: ["Pista de Dança", "Som Profissional", "Área VIP"],
        featured: false,
      },
      {
        id: 7,
        name: "Churrascaria do Gaúcho",
        category: "restaurante",
        description: "Churrasco tradicional gaúcho com carnes nobres e buffet completo.",
        image_url: "public/mineiro.jpg",
        rating: 4.3,
        avg_rating: 4.3,
        total_reviews: 92,
        price_range: 3,
        neighborhood: "centro",
        address: "Av. Brasil, 234 - Centro",
        phone: "(33) 3271-4567",
        latitude: -18.852,
        longitude: -41.948,
        opening_hours: "11:30-15:00, 18:30-23:00",
        is_open: true,
        features: ["Rodízio", "Buffet", "Estacionamento"],
        featured: false,
      },
      {
        id: 8,
        name: "Lanchonete da Esquina",
        category: "restaurante",
        description: "Lanches artesanais e sucos naturais. Ambiente familiar e preços acessíveis.",
        image_url: "public/pizzaria.jpg",
        rating: 4.2,
        avg_rating: 4.2,
        total_reviews: 45,
        price_range: 1,
        neighborhood: "ibituruna",
        address: "Rua das Flores, 567 - Ibituruna",
        phone: "(33) 3271-8901",
        latitude: -18.847,
        longitude: -41.945,
        opening_hours: "06:00-22:00",
        is_open: true,
        features: ["Delivery", "Sucos Naturais", "Preço Justo"],
        featured: false,
      },
    ]
  },

  renderFeaturedEstablishments() {
    const container = Utils.safeQuery("#featuredGrid")
    if (!container) return

    if (AppState.featuredEstablishments.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-store"></i>
          <h3>Nenhum estabelecimento em destaque</h3>
          <p>Em breve teremos estabelecimentos em destaque para você</p>
        </div>
      `
      return
    }

    container.innerHTML = AppState.featuredEstablishments
      .map((establishment) => this.createEstablishmentCard(establishment))
      .join("")

    this.addCardEventListeners()
  },

  renderEstablishments() {
    const container = Utils.safeQuery("#establishmentsList")
    if (!container) return

    if (AppState.filteredEstablishments.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-search"></i>
          <h3>Nenhum estabelecimento encontrado</h3>
          <p>Tente ajustar os filtros ou fazer uma nova busca</p>
          <button class="btn btn-primary" onclick="SearchManager.clearFilters()">
            <i class="fas fa-refresh"></i>
            Limpar Filtros
          </button>
        </div>
      `
      return
    }

    const itemsToShow = AppState.filteredEstablishments.slice(0, AppState.currentPage * CONFIG.ITEMS_PER_PAGE)

    container.innerHTML = itemsToShow.map((establishment) => this.createEstablishmentCard(establishment)).join("")

    this.addCardEventListeners()
    this.updateLoadMoreButton()
  },

  createEstablishmentCard(establishment) {
    const avgRating = establishment.avg_rating || establishment.rating || 0
    const totalReviews = establishment.total_reviews || 0

    return `
      <div class="establishment-card" data-id="${establishment.id}">
        <img src="${establishment.image_url}" 
             alt="${establishment.name}" 
             class="establishment-image"
             loading="lazy"
             onerror="this.src='public/cultural.jpeg'">
        
        <div class="establishment-content">
          <div class="establishment-category">${establishment.category}</div>
          <h3 class="establishment-title">${establishment.name}</h3>
          <p class="establishment-description">${Utils.truncateText(establishment.description, 120)}</p>
          
          <div class="establishment-info">
            <div class="establishment-rating">
              <div class="rating-stars">
                ${Utils.generateStars(avgRating)}
              </div>
              <span class="rating-text">(${totalReviews} avaliações)</span>
            </div>
            <div class="establishment-price">
              ${Utils.formatPrice(establishment.price_range)}
            </div>
          </div>
          
          <div class="establishment-meta">
            <div class="establishment-location">
              <i class="fas fa-map-marker-alt"></i>
              <span>${establishment.neighborhood}</span>
            </div>
            <div class="establishment-actions">
              <button class="action-btn favorite-btn" data-id="${establishment.id}" title="Favoritar">
                <i class="far fa-heart"></i>
              </button>
              <button class="action-btn share-btn" data-id="${establishment.id}" title="Compartilhar">
                <i class="fas fa-share-alt"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    `
  },

  addCardEventListeners() {
    // Click nos cards
    Utils.safeQueryAll(".establishment-card").forEach((card) => {
      card.addEventListener("click", (e) => {
        if (!e.target.closest(".action-btn")) {
          const id = card.dataset.id
          this.showEstablishmentModal(id)
        }
      })
    })

    // Botões de favorito
    Utils.safeQueryAll(".favorite-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.stopPropagation()
        this.toggleFavorite(btn.dataset.id, btn)
      })
    })

    // Botões de compartilhar
    Utils.safeQueryAll(".share-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.stopPropagation()
        this.shareEstablishment(btn.dataset.id)
      })
    })
  },

  async showEstablishmentModal(id) {
    const establishment =
      AppState.establishments.find((e) => e.id == id) || AppState.featuredEstablishments.find((e) => e.id == id)

    if (!establishment) return

    // Increment view count
    try {
      await API.incrementViewCount(id)
    } catch (error) {
      console.error("Erro ao incrementar visualizações:", error)
    }

    const modal = Utils.safeQuery("#establishmentModal")
    const modalBody = Utils.safeQuery("#modalBody")

    if (!modal || !modalBody) return

    const avgRating = establishment.avg_rating || establishment.rating || 0
    const totalReviews = establishment.total_reviews || 0

    modalBody.innerHTML = `
      <div class="modal-establishment">
        <img src="${establishment.image_url}" 
             alt="${establishment.name}" 
             class="modal-establishment-image"
             style="width: 100%; height: 300px; object-fit: cover; border-radius: 1rem; margin-bottom: 2rem;"
             onerror="this.src='public/cultural.jpeg'">
        
        <div class="modal-establishment-content">
          <div class="modal-establishment-header" style="margin-bottom: 2rem;">
            <div class="establishment-category" style="color: var(--primary-color); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">${establishment.category}</div>
            <h2 style="font-size: 2.5rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1rem;">${establishment.name}</h2>
            <div class="establishment-rating" style="display: flex; align-items: center; gap: 1rem;">
              <div class="rating-stars" style="color: var(--accent-color); font-size: 1.2rem;">
                ${Utils.generateStars(avgRating)}
              </div>
              <span style="color: var(--gray-600);">${Utils.formatRating(avgRating)} (${totalReviews} avaliações)</span>
            </div>
          </div>
          
          <div class="modal-establishment-info" style="display: grid; gap: 1rem; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
              <i class="fas fa-map-marker-alt" style="color: var(--primary-color); width: 20px;"></i>
              <span>${establishment.address}</span>
            </div>
            ${
              establishment.phone
                ? `
              <div style="display: flex; align-items: center; gap: 1rem;">
                <i class="fas fa-phone" style="color: var(--primary-color); width: 20px;"></i>
                <span>${establishment.phone}</span>
              </div>
            `
                : ""
            }
            <div style="display: flex; align-items: center; gap: 1rem;">
              <i class="fas fa-clock" style="color: var(--primary-color); width: 20px;"></i>
              <span>${establishment.opening_hours}</span>
              <span style="color: ${establishment.is_open ? "var(--success)" : "var(--error)"}; font-weight: 600;">
                ${establishment.is_open ? "Aberto" : "Fechado"}
              </span>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
              <i class="fas fa-dollar-sign" style="color: var(--primary-color); width: 20px;"></i>
              <span>Faixa de preço: ${Utils.formatPrice(establishment.price_range)}</span>
            </div>
          </div>
          
          <div class="modal-establishment-description" style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem;">Sobre</h3>
            <p style="line-height: 1.6; color: var(--gray-600);">${establishment.description}</p>
          </div>
          
          ${
            establishment.features && establishment.features.length > 0
              ? `
            <div class="modal-establishment-features" style="margin-bottom: 2rem;">
              <h3 style="margin-bottom: 1rem;">Características</h3>
              <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                ${establishment.features
                  .map(
                    (feature) => `
                  <span style="background: var(--gradient-primary); color: white; padding: 0.5rem 1rem; border-radius: 1rem; font-size: 0.9rem;">${feature}</span>
                `,
                  )
                  .join("")}
              </div>
            </div>
          `
              : ""
          }
          
          <div class="modal-establishment-actions" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            ${
              establishment.latitude && establishment.longitude
                ? `
              <button class="btn btn-primary" onclick="EstablishmentManager.getDirections(${establishment.latitude}, ${establishment.longitude})">
                <i class="fas fa-directions"></i>
                Como Chegar
              </button>
            `
                : ""
            }
            
            <button class="btn btn-secondary" onclick="EstablishmentManager.toggleFavorite(${establishment.id})">
              <i class="far fa-heart"></i>
              Favoritar
            </button>
            
            <button class="btn btn-secondary" onclick="EstablishmentManager.shareEstablishment(${establishment.id})">
              <i class="fas fa-share-alt"></i>
              Compartilhar
            </button>
            
            ${
              establishment.phone
                ? `
              <a href="tel:${establishment.phone}" class="btn btn-secondary">
                <i class="fas fa-phone"></i>
                Ligar
              </a>
            `
                : ""
            }
            
            <a href="establishment.php?id=${establishment.id}" class="btn btn-primary">
              <i class="fas fa-external-link-alt"></i>
              Ver Página Completa
            </a>
          </div>
        </div>
      </div>
    `

    modal.classList.add("active")
  },

  async toggleFavorite(id, button = null) {
    try {
      const result = await API.toggleFavorite(id)
      if (result.success) {
        if (button) {
          const icon = button.querySelector("i")
          if (result.favorited) {
            icon.className = "fas fa-heart"
            button.classList.add("active")
            Utils.showNotification("Adicionado aos favoritos!", "success")
          } else {
            icon.className = "far fa-heart"
            button.classList.remove("active")
            Utils.showNotification("Removido dos favoritos!", "info")
          }
        }
      }
    } catch (error) {
      Utils.showNotification("Faça login para favoritar estabelecimentos", "warning")
    }
  },

  shareEstablishment(id) {
    const establishment =
      AppState.establishments.find((e) => e.id == id) || AppState.featuredEstablishments.find((e) => e.id == id)

    if (!establishment) return

    const url = `${window.location.origin}/establishment.php?id=${id}`
    const text = `Confira ${establishment.name} - ${establishment.description}`

    if (navigator.share) {
      navigator.share({
        title: establishment.name,
        text: text,
        url: url,
      })
    } else {
      navigator.clipboard.writeText(url).then(() => {
        Utils.showNotification("Link copiado para a área de transferência!", "success")
      })
    }
  },

  getDirections(lat, lng) {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`
    window.open(url, "_blank")
  },

  updateLoadMoreButton() {
    const button = Utils.safeQuery("#loadMoreBtn")
    if (!button) return

    const totalItems = AppState.filteredEstablishments.length
    const shownItems = AppState.currentPage * CONFIG.ITEMS_PER_PAGE

    if (shownItems >= totalItems) {
      button.style.display = "none"
    } else {
      button.style.display = "block"
      const remaining = totalItems - shownItems
      const toShow = Math.min(CONFIG.ITEMS_PER_PAGE, remaining)
      button.innerHTML = `
        <span>Carregar Mais (${toShow} de ${remaining})</span>
        <i class="fas fa-plus"></i>
      `
    }
  },

  updateStats() {
    const totalElement = Utils.safeQuery("#totalEstablishments")
    const resultsElement = Utils.safeQuery("#resultsCount")

    if (totalElement) {
      totalElement.textContent = `${AppState.establishments.length}+`
    }

    if (resultsElement) {
      const total = AppState.filteredEstablishments.length
      resultsElement.textContent = `${total} estabelecimento${total !== 1 ? "s" : ""} encontrado${total !== 1 ? "s" : ""}`
    }
  },
}

// ===== SEARCH MANAGER =====
const SearchManager = {
  init() {
    const searchInput = Utils.safeQuery("#heroSearchInput")
    const searchBtn = Utils.safeQuery("#heroSearchBtn")
    const suggestionsContainer = Utils.safeQuery("#searchSuggestions")

    if (searchInput) {
      const debouncedSearch = Utils.debounce(this.handleSearch.bind(this), CONFIG.DEBOUNCE_DELAY)
      searchInput.addEventListener("input", debouncedSearch)
      searchInput.addEventListener("focus", () => this.showSuggestions())
      searchInput.addEventListener("blur", () => {
        setTimeout(() => this.hideSuggestions(), 200)
      })
    }

    if (searchBtn) {
      searchBtn.addEventListener("click", () => this.performSearch())
    }

    // Enter key search
    if (searchInput) {
      searchInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          this.performSearch()
        }
      })
    }

    // Sugestões de busca
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("suggestion-item")) {
        const searchInput = Utils.safeQuery("#heroSearchInput")
        if (searchInput) {
          searchInput.value = e.target.textContent
          this.performSearch()
          this.hideSuggestions()
        }
      }
    })
  },

  handleSearch(e) {
    const query = e.target.value.trim()
    AppState.searchQuery = query

    if (query.length >= 2) {
      this.showSuggestions(query)
    } else {
      this.hideSuggestions()
    }

    if (query.length >= 3) {
      this.filterEstablishments()
    }
  },

  showSuggestions(query = "") {
    const container = Utils.safeQuery("#searchSuggestions")
    if (!container) return

    let suggestions = []

    if (query) {
      // Buscar sugestões baseadas no query
      const queryLower = query.toLowerCase()
      suggestions = AppState.establishments
        .filter(
          (est) =>
            est.name.toLowerCase().includes(queryLower) ||
            est.category.toLowerCase().includes(queryLower) ||
            est.description.toLowerCase().includes(queryLower) ||
            est.neighborhood.toLowerCase().includes(queryLower),
        )
        .slice(0, 5)
        .map((est) => est.name)
    } else {
      // Sugestões populares
      suggestions = [
        "Restaurante mineiro",
        "Pizza",
        "Bar com música ao vivo",
        "Café",
        "Teatro",
        "Comida italiana",
        "Hambúrguer",
        "Sushi",
      ]
    }

    if (suggestions.length > 0) {
      container.innerHTML = suggestions.map((suggestion) => `<div class="suggestion-item">${suggestion}</div>`).join("")
      container.style.display = "block"
    } else {
      this.hideSuggestions()
    }
  },

  hideSuggestions() {
    const container = Utils.safeQuery("#searchSuggestions")
    if (container) {
      container.style.display = "none"
    }
  },

  performSearch() {
    const searchInput = Utils.safeQuery("#heroSearchInput")
    if (searchInput) {
      AppState.searchQuery = searchInput.value.trim()
      this.filterEstablishments()
      this.hideSuggestions()

      // Scroll to results
      const establishmentsSection = Utils.safeQuery("#estabelecimentos")
      if (establishmentsSection) {
        Utils.scrollToElement(establishmentsSection)
      }
    }
  },

  filterEstablishments() {
    let filtered = [...AppState.establishments]

    // Filtro de busca por texto
    if (AppState.searchQuery) {
      const query = AppState.searchQuery.toLowerCase()
      filtered = filtered.filter(
        (est) =>
          est.name.toLowerCase().includes(query) ||
          est.category.toLowerCase().includes(query) ||
          est.description.toLowerCase().includes(query) ||
          est.neighborhood.toLowerCase().includes(query),
      )
    }

    // Aplicar outros filtros
    filtered = this.applyFilters(filtered)

    // Ordenação
    this.sortEstablishments(filtered)

    AppState.filteredEstablishments = filtered
    AppState.currentPage = 1
    EstablishmentManager.renderEstablishments()
    EstablishmentManager.updateStats()
    MapManager.updateMarkers()
    this.updateActiveFilters()
  },

  applyFilters(establishments) {
    let filtered = [...establishments]

    // Filtro por categoria
    if (AppState.activeFilters.category) {
      filtered = filtered.filter((est) => est.category === AppState.activeFilters.category)
    }

    // Filtro por bairro
    if (AppState.activeFilters.neighborhood) {
      filtered = filtered.filter((est) => est.neighborhood === AppState.activeFilters.neighborhood)
    }

    // Filtro por preço
    if (AppState.activeFilters.price) {
      filtered = filtered.filter((est) => est.price_range <= Number.parseInt(AppState.activeFilters.price))
    }

    // Filtro por avaliação
    if (AppState.activeFilters.rating) {
      filtered = filtered.filter(
        (est) => (est.avg_rating || est.rating || 0) >= Number.parseInt(AppState.activeFilters.rating),
      )
    }

    // Filtro por avaliação mínima (slider)
    if (AppState.activeFilters.minRating > 0) {
      filtered = filtered.filter((est) => (est.avg_rating || est.rating || 0) >= AppState.activeFilters.minRating)
    }

    // Filtro por estabelecimentos abertos
    if (AppState.activeFilters.openNow) {
      filtered = filtered.filter((est) => est.is_open)
    }

    return filtered
  },

  sortEstablishments(establishments) {
    switch (AppState.sortBy) {
      case "rating":
        establishments.sort((a, b) => (b.avg_rating || b.rating || 0) - (a.avg_rating || a.rating || 0))
        break
      case "name":
        establishments.sort((a, b) => a.name.localeCompare(b.name))
        break
      case "newest":
        establishments.sort((a, b) => b.id - a.id)
        break
      case "price_low":
        establishments.sort((a, b) => a.price_range - b.price_range)
        break
      case "price_high":
        establishments.sort((a, b) => b.price_range - a.price_range)
        break
      default: // relevance
        if (AppState.searchQuery) {
          // Ordenar por relevância baseada na busca
          const query = AppState.searchQuery.toLowerCase()
          establishments.sort((a, b) => {
            const aScore = this.calculateRelevanceScore(a, query)
            const bScore = this.calculateRelevanceScore(b, query)
            return bScore - aScore
          })
        } else {
          // Ordenar por rating quando não há busca
          establishments.sort((a, b) => (b.avg_rating || b.rating || 0) - (a.avg_rating || a.rating || 0))
        }
    }
  },

  calculateRelevanceScore(establishment, query) {
    let score = 0
    const name = establishment.name.toLowerCase()
    const category = establishment.category.toLowerCase()
    const description = establishment.description.toLowerCase()
    const neighborhood = establishment.neighborhood.toLowerCase()

    // Pontuação baseada em onde o termo aparece
    if (name.includes(query)) score += 10
    if (category.includes(query)) score += 8
    if (neighborhood.includes(query)) score += 6
    if (description.includes(query)) score += 5

    // Bonus por rating alto
    score += establishment.avg_rating || establishment.rating || 0

    return score
  },

  updateActiveFilters() {
    const container = Utils.safeQuery("#activeFilters")
    if (!container) return

    const activeFilters = []

    if (AppState.searchQuery) {
      activeFilters.push({
        label: `Busca: "${AppState.searchQuery}"`,
        key: "search",
      })
    }

    if (AppState.activeFilters.category) {
      activeFilters.push({
        label: `Categoria: ${AppState.activeFilters.category}`,
        key: "category",
      })
    }

    if (AppState.activeFilters.neighborhood) {
      activeFilters.push({
        label: `Bairro: ${AppState.activeFilters.neighborhood}`,
        key: "neighborhood",
      })
    }

    if (AppState.activeFilters.price) {
      activeFilters.push({
        label: `Preço: ${Utils.formatPrice(AppState.activeFilters.price)}`,
        key: "price",
      })
    }

    if (AppState.activeFilters.rating) {
      activeFilters.push({
        label: `Avaliação: ${AppState.activeFilters.rating}+ estrelas`,
        key: "rating",
      })
    }

    if (AppState.activeFilters.openNow) {
      activeFilters.push({
        label: "Aberto agora",
        key: "openNow",
      })
    }

    if (activeFilters.length === 0) {
      container.innerHTML = ""
      return
    }

    container.innerHTML = activeFilters
      .map(
        (filter) => `
        <div class="filter-tag">
          <span>${filter.label}</span>
          <button onclick="SearchManager.removeFilter('${filter.key}')" title="Remover filtro">
            <i class="fas fa-times"></i>
          </button>
        </div>
      `,
      )
      .join("")
  },

  removeFilter(key) {
    switch (key) {
      case "search":
        AppState.searchQuery = ""
        const searchInput = Utils.safeQuery("#heroSearchInput")
        if (searchInput) searchInput.value = ""
        break
      case "category":
        AppState.activeFilters.category = ""
        const categoryFilter = Utils.safeQuery("#categoryFilter")
        if (categoryFilter) categoryFilter.value = ""
        break
      case "neighborhood":
        AppState.activeFilters.neighborhood = ""
        const neighborhoodFilter = Utils.safeQuery("#neighborhoodFilter")
        if (neighborhoodFilter) neighborhoodFilter.value = ""
        break
      case "price":
        AppState.activeFilters.price = ""
        const priceFilter = Utils.safeQuery("#priceFilter")
        if (priceFilter) priceFilter.value = ""
        break
      case "rating":
        AppState.activeFilters.rating = ""
        const ratingFilter = Utils.safeQuery("#ratingFilter")
        if (ratingFilter) ratingFilter.value = ""
        break
      case "openNow":
        AppState.activeFilters.openNow = false
        const openNowFilter = Utils.safeQuery("#openNowFilter")
        if (openNowFilter) openNowFilter.checked = false
        break
    }

    this.filterEstablishments()
  },

  clearFilters() {
    AppState.searchQuery = ""
    AppState.activeFilters = {
      category: "",
      neighborhood: "",
      price: "",
      rating: "",
      openNow: false,
      minRating: 0,
    }

    // Reset form elements
    const searchInput = Utils.safeQuery("#heroSearchInput")
    if (searchInput) searchInput.value = ""

    const filters = ["categoryFilter", "neighborhoodFilter", "priceFilter", "ratingFilter"]
    filters.forEach((filterId) => {
      const element = Utils.safeQuery(`#${filterId}`)
      if (element) element.value = ""
    })

    const openNowFilter = Utils.safeQuery("#openNowFilter")
    if (openNowFilter) openNowFilter.checked = false

    this.filterEstablishments()
  },
}

// ===== FILTER MANAGER =====
const FilterManager = {
  init() {
    // Quick categories
    Utils.safeQueryAll(".category-item").forEach((item) => {
      item.addEventListener("click", () => {
        const category = item.dataset.category
        if (category === "todos") {
          AppState.activeFilters.category = ""
        } else {
          AppState.activeFilters.category = category
        }
        SearchManager.filterEstablishments()

        // Scroll to results
        const establishmentsSection = Utils.safeQuery("#estabelecimentos")
        if (establishmentsSection) {
          Utils.scrollToElement(establishmentsSection)
        }
      })
    })

    // Filter selects
    this.initFilterSelects()

    // Apply filters button
    const applyFiltersBtn = Utils.safeQuery("#applyFilters")
    if (applyFiltersBtn) {
      applyFiltersBtn.addEventListener("click", () => {
        SearchManager.filterEstablishments()
      })
    }

    // View controls
    Utils.safeQueryAll(".view-btn, .view-option").forEach((btn) => {
      btn.addEventListener("click", () => {
        const viewType = btn.dataset.view
        if (viewType) {
          Utils.safeQueryAll(".view-btn, .view-option").forEach((b) => b.classList.remove("active"))
          Utils.safeQueryAll(`[data-view="${viewType}"]`).forEach((b) => b.classList.add("active"))
          AppState.viewMode = viewType
          this.updateViewMode()
        }
      })
    })

    // Open now filter
    const openNowFilter = Utils.safeQuery("#openNowFilter")
    if (openNowFilter) {
      openNowFilter.addEventListener("change", (e) => {
        AppState.activeFilters.openNow = e.target.checked
        SearchManager.filterEstablishments()
      })
    }
  },

  initFilterSelects() {
    const filters = [
      { id: "categoryFilter", property: "category" },
      { id: "neighborhoodFilter", property: "neighborhood" },
      { id: "priceFilter", property: "price" },
      { id: "ratingFilter", property: "rating" },
      { id: "sortFilter", property: "sortBy", isSort: true },
    ]

    filters.forEach((filter) => {
      const element = Utils.safeQuery(`#${filter.id}`)
      if (element) {
        element.addEventListener("change", (e) => {
          if (filter.isSort) {
            AppState.sortBy = e.target.value
          } else {
            AppState.activeFilters[filter.property] = e.target.value
          }
          SearchManager.filterEstablishments()
        })
      }
    })
  },

  updateViewMode() {
    const grid = Utils.safeQuery("#establishmentsList")
    if (grid) {
      grid.className = `establishments-grid ${AppState.viewMode}-view`
    }
  },
}

// ===== MAP MANAGER =====
const MapManager = {
  init() {
    // Delay map initialization to ensure DOM is ready
    setTimeout(() => {
      this.initMap()
      this.initMapControls()
    }, 1000)
  },

  initMap() {
    const mapContainer = Utils.safeQuery("#map")
    if (!mapContainer) return

    // Remove loading indicator
    const loadingIndicator = mapContainer.querySelector(".map-loading")
    if (loadingIndicator) {
      loadingIndicator.remove()
    }

    try {
      // Check if Leaflet is available
      if (typeof L === "undefined") {
        console.error("Leaflet não está carregado")
        mapContainer.innerHTML = `
          <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--gray-500);">
            <div style="text-align: center;">
              <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
              <p>Erro ao carregar o mapa</p>
            </div>
          </div>
        `
        return
      }

      // Initialize Leaflet map
      AppState.map = L.map("map").setView(CONFIG.MAP_CENTER, CONFIG.MAP_ZOOM)

      // Add OpenStreetMap tiles
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
        maxZoom: 19,
      }).addTo(AppState.map)

      // Add markers
      this.updateMarkers()

      // Map events
      AppState.map.on("zoomend", () => {
        this.updateMarkerVisibility()
      })
    } catch (error) {
      console.error("Erro ao inicializar mapa:", error)
      mapContainer.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--gray-500);">
          <div style="text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
            <p>Erro ao carregar o mapa</p>
          </div>
        </div>
      `
    }
  },

  initMapControls() {
    // Map filter buttons
    Utils.safeQueryAll(".map-filter-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        Utils.safeQueryAll(".map-filter-btn").forEach((b) => b.classList.remove("active"))
        btn.classList.add("active")
        this.filterMapMarkers(btn.dataset.category)
      })
    })

    // Center map button
    const centerBtn = Utils.safeQuery("#centerMapBtn")
    if (centerBtn) {
      centerBtn.addEventListener("click", () => {
        if (AppState.map) {
          AppState.map.setView(CONFIG.MAP_CENTER, CONFIG.MAP_ZOOM)
        }
      })
    }

    // Fullscreen button
    const fullscreenBtn = Utils.safeQuery("#fullscreenMapBtn")
    if (fullscreenBtn) {
      fullscreenBtn.addEventListener("click", () => {
        this.toggleFullscreen()
      })
    }
  },

  updateMarkers() {
    if (!AppState.map) return

    // Clear existing markers
    AppState.markers.forEach((marker) => AppState.map.removeLayer(marker))
    AppState.markers = []

    // Add new markers
    const establishments =
      AppState.filteredEstablishments.length > 0 ? AppState.filteredEstablishments : AppState.establishments

    establishments.forEach((establishment) => {
      if (establishment.latitude && establishment.longitude) {
        const marker = L.marker([establishment.latitude, establishment.longitude]).addTo(AppState.map)

        const popupContent = `
          <div class="map-popup" style="min-width: 250px;">
            <img src="${establishment.image_url}" 
                 alt="${establishment.name}" 
                 style="width: 100%; height: 120px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 1rem;"
                 onerror="this.src='public/cultural.jpeg'">
            <div class="popup-content">
              <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: var(--gray-900);">${establishment.name}</h4>
              <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: var(--primary-color); text-transform: uppercase; font-weight: 600;">${establishment.category}</p>
              <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <div style="color: var(--accent-color);">
                  ${Utils.generateStars(establishment.avg_rating || establishment.rating || 0)}
                </div>
                <span style="font-size: 0.8rem; color: var(--gray-600);">(${establishment.total_reviews || 0})</span>
              </div>
              <button onclick="EstablishmentManager.showEstablishmentModal(${establishment.id})" 
                      style="width: 100%; padding: 0.5rem 1rem; background: var(--gradient-primary); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">
                Ver Detalhes
              </button>
            </div>
          </div>
        `

        marker.bindPopup(popupContent, {
          maxWidth: 300,
          className: "custom-popup",
        })

        AppState.markers.push(marker)
      }
    })

    // Fit map to markers if there are any
    if (AppState.markers.length > 0) {
      const group = new L.featureGroup(AppState.markers)
      AppState.map.fitBounds(group.getBounds().pad(0.1))
    }
  },

  filterMapMarkers(category) {
    if (!AppState.map) return

    // Clear all markers
    AppState.markers.forEach((marker) => AppState.map.removeLayer(marker))

    // Filter establishments
    const filtered =
      category === "todos"
        ? AppState.establishments
        : AppState.establishments.filter((est) => est.category === category)

    // Add filtered markers
    const filteredMarkers = AppState.markers.filter((marker, index) => {
      const establishment = AppState.establishments[index]
      return category === "todos" || establishment.category === category
    })

    filteredMarkers.forEach((marker) => {
      AppState.map.addLayer(marker)
    })
  },

  updateMarkerVisibility() {
    // Update marker visibility based on zoom level
    const zoom = AppState.map.getZoom()
    const showLabels = zoom > 14

    AppState.markers.forEach((marker) => {
      // You can customize marker appearance based on zoom level here
    })
  },

  toggleFullscreen() {
    const mapContainer = Utils.safeQuery("#map")
    if (!mapContainer) return

    if (!document.fullscreenElement) {
      mapContainer
        .requestFullscreen()
        .then(() => {
          setTimeout(() => {
            if (AppState.map) {
              AppState.map.invalidateSize()
            }
          }, 100)
        })
        .catch((error) => {
          console.error("Erro ao entrar em tela cheia:", error)
        })
    } else {
      document.exitFullscreen()
    }
  },
}

// ===== LOCATION MANAGER =====
const LocationManager = {
  init() {
    const locationBtn = Utils.safeQuery("#locationBtn")
    if (locationBtn) {
      locationBtn.addEventListener("click", () => this.getCurrentLocation())
    }
  },

  getCurrentLocation() {
    if (!navigator.geolocation) {
      Utils.showNotification("Geolocalização não é suportada neste navegador", "error")
      return
    }

    Utils.showNotification("Obtendo sua localização...", "info", 2000)

    navigator.geolocation.getCurrentPosition(
      (position) => {
        AppState.userLocation = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        }

        this.updateDistances()
        Utils.showNotification("Localização obtida com sucesso!", "success")

        // Center map on user location
        if (AppState.map) {
          AppState.map.setView([AppState.userLocation.lat, AppState.userLocation.lng], 15)

          // Add user location marker
          const userIcon = L.divIcon({
            className: "user-location-marker",
            html: '<i class="fas fa-user" style="color: white; font-size: 1rem;"></i>',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
          })

          L.marker([AppState.userLocation.lat, AppState.userLocation.lng], { icon: userIcon })
            .addTo(AppState.map)
            .bindPopup("Você está aqui")
        }
      },
      (error) => {
        let message = "Erro ao obter localização"
        switch (error.code) {
          case error.PERMISSION_DENIED:
            message = "Permissão de localização negada"
            break
          case error.POSITION_UNAVAILABLE:
            message = "Localização indisponível"
            break
          case error.TIMEOUT:
            message = "Tempo limite para obter localização"
            break
        }
        Utils.showNotification(message, "error")
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000, // 5 minutes
      },
    )
  },

  updateDistances() {
    if (!AppState.userLocation) return

    AppState.establishments.forEach((establishment) => {
      if (establishment.latitude && establishment.longitude) {
        establishment.distance = this.calculateDistance(
          AppState.userLocation.lat,
          AppState.userLocation.lng,
          establishment.latitude,
          establishment.longitude,
        )
      }
    })

    // Re-render establishments with distances
    EstablishmentManager.renderEstablishments()
  },

  calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371 // Earth's radius in km
    const dLat = this.deg2rad(lat2 - lat1)
    const dLon = this.deg2rad(lon2 - lon1)
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2)
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
    return R * c * 1000 // Return in meters
  },

  deg2rad(deg) {
    return deg * (Math.PI / 180)
  },
}

// ===== PROMOTIONS MANAGER =====
const PromotionsManager = {
  async loadPromotions() {
    try {
      const data = await API.getPromotions()
      AppState.promotions = data.promotions || this.getMockPromotions()
      this.renderPromotions()
    } catch (error) {
      console.error("Erro ao carregar promoções:", error)
      AppState.promotions = this.getMockPromotions()
      this.renderPromotions()
    }
  },

  getMockPromotions() {
    return [
      {
        id: 1,
        title: "Pizza em Dobro",
        description: "Compre uma pizza grande e ganhe outra igual grátis!",
        discount_percentage: 50,
        establishment_name: "Pizzaria Panorâmica",
        establishment_image: "public/pizzaria.jpg",
        end_date: "2024-12-31",
      },
      {
        id: 2,
        title: "Happy Hour Especial",
        description: "Cerveja e petiscos com 30% de desconto das 17h às 19h",
        discount_percentage: 30,
        establishment_name: "Bar Música Ao Vivo",
        establishment_image: "public/cultural.jpeg",
        end_date: "2024-12-25",
      },
    ]
  },

  renderPromotions() {
    const container = Utils.safeQuery("#promotionsSlider")
    if (!container) return

    if (AppState.promotions.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-tags"></i>
          <h3>Nenhuma promoção ativa</h3>
          <p>Em breve teremos promoções especiais para você</p>
        </div>
      `
      return
    }

    container.innerHTML = AppState.promotions
      .slice(0, 6) // Show only first 6
      .map(
        (promotion) => `
        <div class="promotion-card">
          <div class="promotion-badge">
            ${promotion.discount_percentage ? `${promotion.discount_percentage}% OFF` : "OFERTA"}
          </div>
          <img src="${promotion.establishment_image || "public/cultural.jpeg"}" 
               alt="${promotion.establishment_name}" 
               class="promotion-image">
          <div class="promotion-content">
            <h4>${promotion.title}</h4>
            <p>${Utils.truncateText(promotion.description, 80)}</p>
            <div class="promotion-meta">
              <span class="establishment-name">${promotion.establishment_name}</span>
              <span class="promotion-validity">Até ${Utils.formatDate(promotion.end_date)}</span>
            </div>
          </div>
        </div>
      `,
      )
      .join("")
  },
}

// ===== EVENTS MANAGER =====
const EventsManager = {
  async loadEvents() {
    try {
      const data = await API.getEvents()
      AppState.events = data.events || this.getMockEvents()
      this.renderEvents()
    } catch (error) {
      console.error("Erro ao carregar eventos:", error)
      AppState.events = this.getMockEvents()
      this.renderEvents()
    }
  },

  getMockEvents() {
    return [
      {
        id: 1,
        title: "Show de Rock Nacional",
        description: "Grandes bandas do rock nacional se apresentam em uma noite inesquecível",
        event_date: "2024-12-20",
        start_time: "20:00",
        establishment_name: "Casa de Shows Municipal",
        image_url: "public/cultural.jpeg",
      },
      {
        id: 2,
        title: "Noite do Samba",
        description: "Samba de raiz com os melhores músicos da região",
        event_date: "2024-12-22",
        start_time: "21:00",
        establishment_name: "Bar Música Ao Vivo",
        image_url: "public/cultural.jpeg",
      },
    ]
  },

  renderEvents() {
    const container = Utils.safeQuery("#eventsSlider")
    if (!container) return

    if (AppState.events.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-calendar"></i>
          <h3>Nenhum evento programado</h3>
          <p>Em breve teremos eventos incríveis para você</p>
        </div>
      `
      return
    }

    container.innerHTML = AppState.events
      .slice(0, 6) // Show only first 6
      .map(
        (event) => `
        <div class="event-card">
          <div class="event-date">
            <span class="day">${new Date(event.event_date).getDate()}</span>
            <span class="month">${new Date(event.event_date).toLocaleDateString("pt-BR", { month: "short" })}</span>
          </div>
          <img src="${event.image_url || "public/cultural.jpeg"}" 
               alt="${event.title}" 
               class="event-image">
          <div class="event-content">
            <h4>${event.title}</h4>
            <p>${Utils.truncateText(event.description, 80)}</p>
            <div class="event-meta">
              <span class="establishment-name">${event.establishment_name}</span>
              <span class="event-time">
                ${event.start_time ? new Date(`2000-01-01 ${event.start_time}`).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" }) : ""}
              </span>
            </div>
          </div>
        </div>
      `,
      )
      .join("")
  },
}

// ===== UI MANAGER =====
const UIManager = {
  init() {
    this.initHeader()
    this.initModal()
    this.initBackToTop()
    this.initMobileMenu()
    this.initLoadMore()
    this.initThemeToggle()
    this.initScrollEffects()
    this.initNavigation()
    this.initNewsletterForm()
    this.checkUserAuth()
  },

  initHeader() {
    const header = Utils.safeQuery("#header")
    if (!header) return

    window.addEventListener("scroll", () => {
      if (window.scrollY > 100) {
        header.classList.add("scrolled")
      } else {
        header.classList.remove("scrolled")
      }
    })
  },

  initModal() {
    const modal = Utils.safeQuery("#establishmentModal")
    const closeBtn = Utils.safeQuery("#modalClose")
    const overlay = modal?.querySelector(".modal-overlay")

    if (closeBtn) {
      closeBtn.addEventListener("click", () => {
        modal.classList.remove("active")
      })
    }

    if (overlay) {
      overlay.addEventListener("click", () => {
        modal.classList.remove("active")
      })
    }

    // Close modal with Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal?.classList.contains("active")) {
        modal.classList.remove("active")
      }
    })
  },

  initBackToTop() {
    const backToTopBtn = Utils.safeQuery("#backToTop")

    if (backToTopBtn) {
      window.addEventListener("scroll", () => {
        if (window.pageYOffset > 300) {
          backToTopBtn.classList.add("visible")
        } else {
          backToTopBtn.classList.remove("visible")
        }
      })

      backToTopBtn.addEventListener("click", () => {
        window.scrollTo({
          top: 0,
          behavior: "smooth",
        })
      })
    }
  },

  initMobileMenu() {
    const toggle = Utils.safeQuery("#mobileMenuToggle")
    const nav = Utils.safeQuery("#mainNav")

    if (toggle && nav) {
      toggle.addEventListener("click", () => {
        nav.classList.toggle("mobile-active")
        toggle.classList.toggle("active")
      })
    }
  },

  initLoadMore() {
    const loadMoreBtn = Utils.safeQuery("#loadMoreBtn")

    if (loadMoreBtn) {
      loadMoreBtn.addEventListener("click", () => {
        AppState.currentPage++
        EstablishmentManager.renderEstablishments()

        // Smooth scroll to new content
        setTimeout(() => {
          const newCards = Utils.safeQueryAll(".establishment-card")
          const firstNewCard =
            newCards[Math.min(newCards.length - 1, (AppState.currentPage - 1) * CONFIG.ITEMS_PER_PAGE)]
          if (firstNewCard) {
            Utils.scrollToElement(firstNewCard, 100)
          }
        }, 100)
      })
    }
  },

  initThemeToggle() {
    const themeToggle = Utils.safeQuery("#themeToggle")

    if (themeToggle) {
      // Check saved theme
      const savedTheme = localStorage.getItem("theme") || "light"
      document.documentElement.setAttribute("data-theme", savedTheme)

      const icon = themeToggle.querySelector("i")
      if (icon) {
        icon.className = savedTheme === "dark" ? "fas fa-sun" : "fas fa-moon"
      }

      themeToggle.addEventListener("click", () => {
        const currentTheme = document.documentElement.getAttribute("data-theme")
        const newTheme = currentTheme === "dark" ? "light" : "dark"

        document.documentElement.setAttribute("data-theme", newTheme)
        localStorage.setItem("theme", newTheme)

        const icon = themeToggle.querySelector("i")
        if (icon) {
          icon.className = newTheme === "dark" ? "fas fa-sun" : "fas fa-moon"
        }

        Utils.showNotification(`Tema ${newTheme === "dark" ? "escuro" : "claro"} ativado`, "success", 2000)
      })
    }
  },

  initScrollEffects() {
    // Intersection Observer for animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("fade-in")
        }
      })
    }, observerOptions)

    // Observe elements for animation
    const elementsToAnimate = Utils.safeQueryAll(".section-header, .establishment-card, .feature-card")
    elementsToAnimate.forEach((el) => observer.observe(el))
  },

  initNavigation() {
    // Smooth scroll for navigation links
    Utils.safeQueryAll('a[href^="#"]').forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault()
        const targetId = link.getAttribute("href").substring(1)
        const targetElement = Utils.safeQuery(`#${targetId}`)

        if (targetElement) {
          Utils.scrollToElement(targetElement)

          // Update active nav link
          Utils.safeQueryAll(".nav-link").forEach((navLink) => {
            navLink.classList.remove("active")
          })
          link.classList.add("active")
        }
      })
    })

    // Update active nav link on scroll
    window.addEventListener("scroll", () => {
      const sections = Utils.safeQueryAll("section[id]")
      const scrollPos = window.scrollY + 100

      sections.forEach((section) => {
        const sectionTop = section.offsetTop
        const sectionHeight = section.offsetHeight
        const sectionId = section.getAttribute("id")

        if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
          Utils.safeQueryAll(".nav-link").forEach((link) => {
            link.classList.remove("active")
          })

          const activeLink = Utils.safeQuery(`a[data-section="${sectionId}"]`)
          if (activeLink) {
            activeLink.classList.add("active")
          }
        }
      })
    })
  },

  initNewsletterForm() {
    const form = Utils.safeQuery("#newsletterForm")

    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault()

        const email = form.querySelector('input[type="email"]').value

        if (email) {
          // Simulate newsletter subscription
          Utils.showNotification("Obrigado! Você foi inscrito na nossa newsletter.", "success")
          form.reset()
        }
      })
    }
  },

  checkUserAuth() {
    // Check if user is logged in (simulated)
    const isLoggedIn = localStorage.getItem("user_logged_in") === "true"
    const userMenu = Utils.safeQuery("#userMenu")
    const authButtons = Utils.safeQuery("#authButtons")

    if (isLoggedIn) {
      if (userMenu) userMenu.style.display = "block"
      if (authButtons) authButtons.style.display = "none"

      // Load user favorites
      this.loadUserFavorites()
    } else {
      if (userMenu) userMenu.style.display = "none"
      if (authButtons) authButtons.style.display = "flex"
    }
  },

  async loadUserFavorites() {
    try {
      const data = await API.getUserFavorites()
      if (data.success) {
        // Update favorite buttons
        data.favorites.forEach((establishmentId) => {
          const favoriteBtn = Utils.safeQuery(`[data-id="${establishmentId}"].favorite-btn`)
          if (favoriteBtn) {
            const icon = favoriteBtn.querySelector("i")
            if (icon) {
              icon.className = "fas fa-heart"
              favoriteBtn.classList.add("active")
            }
          }
        })
      }
    } catch (error) {
      console.error("Erro ao carregar favoritos:", error)
    }
  },
}

// ===== LOADING MANAGER =====
const LoadingManager = {
  init() {
    this.showLoadingScreen()

    // Hide loading screen when page is fully loaded
    window.addEventListener("load", () => {
      setTimeout(() => {
        this.hideLoadingScreen()
      }, 1500)
    })
  },

  showLoadingScreen() {
    const loadingScreen = Utils.safeQuery("#loadingScreen")
    if (loadingScreen) {
      loadingScreen.classList.remove("hidden")
    }
  },

  hideLoadingScreen() {
    const loadingScreen = Utils.safeQuery("#loadingScreen")
    if (loadingScreen) {
      loadingScreen.classList.add("hidden")

      // Remove from DOM after animation
      setTimeout(() => {
        if (loadingScreen.parentNode) {
          loadingScreen.parentNode.removeChild(loadingScreen)
        }
      }, 500)
    }
  },
}

// ===== APPLICATION INITIALIZATION =====
document.addEventListener("DOMContentLoaded", () => {
  console.log("🚀 Iniciando O Que Fazer em GV...")

  // Initialize loading screen
  LoadingManager.init()

  // Initialize all managers
  UIManager.init()
  SearchManager.init()
  FilterManager.init()
  LocationManager.init()

  // Load data
  EstablishmentManager.loadEstablishments()
  EstablishmentManager.loadFeaturedEstablishments()
  PromotionsManager.loadPromotions()
  EventsManager.loadEvents()

  // Initialize map after a delay to ensure DOM is ready
  MapManager.init()

  // Mark as initialized
  AppState.isInitialized = true

  console.log("✅ Aplicação inicializada com sucesso!")
})

// ===== GLOBAL ERROR HANDLING =====
window.addEventListener("error", (e) => {
  console.error("Erro global:", e.error)
  if (AppState.isInitialized) {
    Utils.showNotification("Ocorreu um erro inesperado. Tente recarregar a página.", "error")
  }
})

window.addEventListener("unhandledrejection", (e) => {
  console.error("Promise rejeitada:", e.reason)
  if (AppState.isInitialized) {
    Utils.showNotification("Erro de conexão. Verifique sua internet.", "error")
  }
})

// ===== EXPORT FOR GLOBAL ACCESS =====
window.EstablishmentManager = EstablishmentManager
window.SearchManager = SearchManager
window.FilterManager = FilterManager
window.MapManager = MapManager
window.LocationManager = LocationManager
window.UIManager = UIManager
window.Utils = Utils
window.AppState = AppState
// ===== GLOBAL VARIABLES =====
let map
let establishments = []
let filteredEstablishments = []
let currentPage = 1
const itemsPerPage = 12
let isLoading = false

// ===== DOM ELEMENTS =====
const loadingScreen = document.getElementById("loadingScreen")
const header = document.getElementById("header")
const mobileMenuToggle = document.getElementById("mobileMenuToggle")
const mainNav = document.getElementById("mainNav")
const backToTop = document.getElementById("backToTop")
const themeToggle = document.getElementById("themeToggle")

// ===== INITIALIZATION =====
document.addEventListener("DOMContentLoaded", () => {
  initializeApp()
})

function initializeApp() {
  // Hide loading screen
  setTimeout(() => {
    loadingScreen.classList.add("hidden")
  }, 2000)

  // Initialize components
  initializeHeader()
  initializeNavigation()
  initializeSearch()
  initializeFilters()
  initializeMap()
  initializeEstablishments()
  initializePromotions()
  initializeEvents()
  initializeNewsletter()
  initializeTheme()
  initializeScrollEffects()
  initializeAuth()
}

// ===== HEADER & NAVIGATION =====
function initializeHeader() {
  // Header scroll effect
  window.addEventListener("scroll", () => {
    if (window.scrollY > 100) {
      header.classList.add("scrolled")
    } else {
      header.classList.remove("scrolled")
    }
  })

  // Mobile menu toggle
  mobileMenuToggle.addEventListener("click", () => {
    mobileMenuToggle.classList.toggle("active")
    mainNav.classList.toggle("mobile-active")
  })

  // Close mobile menu when clicking outside
  document.addEventListener("click", (e) => {
    if (!header.contains(e.target)) {
      mobileMenuToggle.classList.remove("active")
      mainNav.classList.remove("mobile-active")
    }
  })
}

function initializeNavigation() {
  // Smooth scrolling for navigation links
  const navLinks = document.querySelectorAll(".nav-link[data-section]")

  navLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault()
      const targetId = link.getAttribute("data-section")
      const targetElement = document.getElementById(targetId)

      if (targetElement) {
        const offsetTop = targetElement.offsetTop - 80
        window.scrollTo({
          top: offsetTop,
          behavior: "smooth",
        })

        // Update active nav link
        navLinks.forEach((l) => l.classList.remove("active"))
        link.classList.add("active")

        // Close mobile menu
        mobileMenuToggle.classList.remove("active")
        mainNav.classList.remove("mobile-active")
      }
    })
  })

  // Update active nav link on scroll
  window.addEventListener("scroll", updateActiveNavLink)
}

function updateActiveNavLink() {
  const sections = document.querySelectorAll("section[id]")
  const navLinks = document.querySelectorAll(".nav-link[data-section]")

  let currentSection = ""

  sections.forEach((section) => {
    const sectionTop = section.offsetTop - 100
    const sectionHeight = section.offsetHeight

    if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
      currentSection = section.getAttribute("id")
    }
  })

  navLinks.forEach((link) => {
    link.classList.remove("active")
    if (link.getAttribute("data-section") === currentSection) {
      link.classList.add("active")
    }
  })
}

// ===== SEARCH FUNCTIONALITY =====
function initializeSearch() {
  const heroSearchInput = document.getElementById("heroSearchInput")
  const heroSearchBtn = document.getElementById("heroSearchBtn")
  const searchSuggestions = document.getElementById("searchSuggestions")
  const locationBtn = document.getElementById("locationBtn")

  // Search input event listeners
  heroSearchInput.addEventListener("input", handleSearchInput)
  heroSearchInput.addEventListener("focus", showSearchSuggestions)
  heroSearchInput.addEventListener("blur", hideSearchSuggestions)

  // Search button
  heroSearchBtn.addEventListener("click", performSearch)

  // Enter key search
  heroSearchInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      performSearch()
    }
  })

  // Location button
  locationBtn.addEventListener("click", getUserLocation)

  // Quick categories
  const categoryItems = document.querySelectorAll(".category-item")
  categoryItems.forEach((item) => {
    item.addEventListener("click", () => {
      const category = item.getAttribute("data-category")
      filterByCategory(category)
    })
  })
}

function handleSearchInput(e) {
  const query = e.target.value.trim()

  if (query.length > 2) {
    showSearchSuggestions()
    updateSearchSuggestions(query)
  } else {
    hideSearchSuggestions()
  }
}

function showSearchSuggestions() {
  const searchSuggestions = document.getElementById("searchSuggestions")
  searchSuggestions.style.display = "block"
}

function hideSearchSuggestions() {
  setTimeout(() => {
    const searchSuggestions = document.getElementById("searchSuggestions")
    searchSuggestions.style.display = "none"
  }, 200)
}

function updateSearchSuggestions(query) {
  const searchSuggestions = document.getElementById("searchSuggestions")

  // Mock suggestions - replace with real API call
  const suggestions = [
    "Restaurante Mineiro",
    "Pizzaria Panorâmica",
    "Bar com Música ao Vivo",
    "Café Aconchegante",
    "Teatro Municipal",
  ].filter((item) => item.toLowerCase().includes(query.toLowerCase()))

  searchSuggestions.innerHTML = suggestions
    .map((suggestion) => `<div class="suggestion-item" onclick="selectSuggestion('${suggestion}')">${suggestion}</div>`)
    .join("")
}

function selectSuggestion(suggestion) {
  const heroSearchInput = document.getElementById("heroSearchInput")
  heroSearchInput.value = suggestion
  hideSearchSuggestions()
  performSearch()
}

function performSearch() {
  const query = document.getElementById("heroSearchInput").value.trim()

  if (query) {
    // Scroll to establishments section
    const establishmentsSection = document.getElementById("estabelecimentos")
    establishmentsSection.scrollIntoView({ behavior: "smooth" })

    // Filter establishments
    filterEstablishments({ search: query })

    showNotification("Busca realizada", `Resultados para: "${query}"`, "info")
  }
}

function getUserLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = position.coords.latitude
        const lng = position.coords.longitude

        // Update map center
        if (map) {
          map.setView([lat, lng], 15)
        }

        showNotification("Localização", "Localização atualizada com sucesso!", "success")
      },
      (error) => {
        showNotification("Erro", "Não foi possível obter sua localização.", "error")
      },
    )
  } else {
    showNotification("Erro", "Geolocalização não é suportada pelo seu navegador.", "error")
  }
}

function filterByCategory(category) {
  if (category === "todos") {
    filteredEstablishments = [...establishments]
  } else {
    filteredEstablishments = establishments.filter((est) => est.category === category)
  }

  // Scroll to establishments section
  const establishmentsSection = document.getElementById("estabelecimentos")
  establishmentsSection.scrollIntoView({ behavior: "smooth" })

  renderEstablishments()
  updateResultsCount()
}

// ===== FILTERS =====
function initializeFilters() {
  const applyFiltersBtn = document.getElementById("applyFilters")
  const filterSelects = document.querySelectorAll(".filter-select")
  const viewBtns = document.querySelectorAll(".view-btn")
  const viewOptions = document.querySelectorAll(".view-option")

  // Apply filters button
  applyFiltersBtn.addEventListener("click", applyFilters)

  // Filter change events
  filterSelects.forEach((select) => {
    select.addEventListener("change", applyFilters)
  })

  // View toggle buttons
  viewBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      viewBtns.forEach((b) => b.classList.remove("active"))
      btn.classList.add("active")

      const view = btn.getAttribute("data-view")
      toggleView(view)
    })
  })

  viewOptions.forEach((btn) => {
    btn.addEventListener("click", () => {
      viewOptions.forEach((b) => b.classList.remove("active"))
      btn.classList.add("active")

      const view = btn.getAttribute("data-view")
      toggleView(view)
    })
  })
}

function applyFilters() {
  const filters = {
    category: document.getElementById("categoryFilter").value,
    neighborhood: document.getElementById("neighborhoodFilter").value,
    price: document.getElementById("priceFilter").value,
    rating: document.getElementById("ratingFilter").value,
    sort: document.getElementById("sortFilter").value,
    openNow: document.getElementById("openNowFilter").checked,
  }

  filterEstablishments(filters)
  updateActiveFilters(filters)
}

function filterEstablishments(filters) {
  filteredEstablishments = establishments.filter((establishment) => {
    // Category filter
    if (filters.category && establishment.category !== filters.category) {
      return false
    }

    // Neighborhood filter
    if (filters.neighborhood && establishment.neighborhood !== filters.neighborhood) {
      return false
    }

    // Price filter
    if (filters.price && establishment.priceRange !== Number.parseInt(filters.price)) {
      return false
    }

    // Rating filter
    if (filters.rating && establishment.rating < Number.parseFloat(filters.rating)) {
      return false
    }

    // Search filter
    if (filters.search) {
      const searchTerm = filters.search.toLowerCase()
      return (
        establishment.name.toLowerCase().includes(searchTerm) ||
        establishment.description.toLowerCase().includes(searchTerm)
      )
    }

    // Open now filter
    if (filters.openNow && !establishment.isOpen) {
      return false
    }

    return true
  })

  // Sort results
  if (filters.sort) {
    sortEstablishments(filters.sort)
  }

  renderEstablishments()
  updateResultsCount()
}

function sortEstablishments(sortBy) {
  switch (sortBy) {
    case "rating":
      filteredEstablishments.sort((a, b) => b.rating - a.rating)
      break
    case "name":
      filteredEstablishments.sort((a, b) => a.name.localeCompare(b.name))
      break
    case "newest":
      filteredEstablishments.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
      break
    default:
      // Keep original order for relevance
      break
  }
}

function updateActiveFilters(filters) {
  const activeFiltersContainer = document.getElementById("activeFilters")
  activeFiltersContainer.innerHTML = ""

  Object.entries(filters).forEach(([key, value]) => {
    if (value && value !== "" && key !== "sort") {
      const filterTag = document.createElement("div")
      filterTag.className = "filter-tag"

      let displayValue = value
      if (key === "openNow") displayValue = "Aberto agora"
      if (key === "price") displayValue = "$".repeat(Number.parseInt(value))
      if (key === "rating") displayValue = `${value}+ estrelas`

      filterTag.innerHTML = `
                ${key}: ${displayValue}
                <button onclick="removeFilter('${key}')">&times;</button>
            `

      activeFiltersContainer.appendChild(filterTag)
    }
  })
}

function removeFilter(filterKey) {
  const filterElement = document.getElementById(`${filterKey}Filter`)
  if (filterElement) {
    if (filterElement.type === "checkbox") {
      filterElement.checked = false
    } else {
      filterElement.value = ""
    }
    applyFilters()
  }
}

function toggleView(view) {
  const establishmentsGrid = document.getElementById("establishmentsList")

  if (view === "list") {
    establishmentsGrid.classList.add("list-view")
  } else {
    establishmentsGrid.classList.remove("list-view")
  }
}

// ===== MAP FUNCTIONALITY =====
function initializeMap() {
  // Initialize Leaflet map
  map = L.map("map").setView([-18.8506, -41.9517], 13) // Governador Valadares coordinates

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
  }).addTo(map)

  // Map controls
  const centerMapBtn = document.getElementById("centerMapBtn")
  const fullscreenMapBtn = document.getElementById("fullscreenMapBtn")
  const mapFilterBtns = document.querySelectorAll(".map-filter-btn")

  centerMapBtn.addEventListener("click", () => {
    map.setView([-18.8506, -41.9517], 13)
  })

  fullscreenMapBtn.addEventListener("click", toggleFullscreenMap)

  // Map filter buttons
  mapFilterBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      mapFilterBtns.forEach((b) => b.classList.remove("active"))
      btn.classList.add("active")

      const category = btn.getAttribute("data-category")
      filterMapMarkers(category)
    })
  })

  // Add markers for establishments
  addMapMarkers()
}

function addMapMarkers() {
  // Mock establishment data - replace with real data
  const mockEstablishments = [
    {
      id: 1,
      name: "Restaurante Mineiro",
      category: "restaurante",
      lat: -18.8506,
      lng: -41.9517,
      rating: 4.5,
    },
    {
      id: 2,
      name: "Pizzaria Panorâmica",
      category: "restaurante",
      lat: -18.8556,
      lng: -41.9567,
      rating: 4.2,
    },
    {
      id: 3,
      name: "Bar com Música",
      category: "bar",
      lat: -18.8456,
      lng: -41.9467,
      rating: 4.0,
    },
  ]

  mockEstablishments.forEach((establishment) => {
    const marker = L.marker([establishment.lat, establishment.lng]).addTo(map)

    marker.bindPopup(`
            <div class="map-popup">
                <h4>${establishment.name}</h4>
                <p>Categoria: ${establishment.category}</p>
                <p>Avaliação: ${establishment.rating} ⭐</p>
                <button onclick="viewEstablishment(${establishment.id})">Ver Detalhes</button>
            </div>
        `)
  })
}

function filterMapMarkers(category) {
  // Implementation for filtering map markers by category
  console.log("Filtering map markers by category:", category)
}

function toggleFullscreenMap() {
  const mapContainer = document.querySelector(".map-container-wrapper")

  if (!document.fullscreenElement) {
    mapContainer.requestFullscreen().then(() => {
      setTimeout(() => map.invalidateSize(), 100)
    })
  } else {
    document.exitFullscreen().then(() => {
      setTimeout(() => map.invalidateSize(), 100)
    })
  }
}

// ===== ESTABLISHMENTS =====
function initializeEstablishments() {
  loadEstablishments()

  const loadMoreBtn = document.getElementById("loadMoreBtn")
  loadMoreBtn.addEventListener("click", loadMoreEstablishments)
}

function loadEstablishments() {
  // Mock data - replace with real API call
  establishments = [
    {
      id: 1,
      name: "Restaurante Mineiro",
      category: "restaurante",
      description: "Comida mineira tradicional com sabor caseiro e ambiente acolhedor.",
      image: "public/restaurante-mineiro.png",
      rating: 4.5,
      reviewCount: 127,
      priceRange: 2,
      neighborhood: "centro",
      isOpen: true,
      createdAt: "2024-01-15",
    },
    {
      id: 2,
      name: "Pizzaria Panorâmica",
      category: "restaurante",
      description: "Pizzas artesanais com vista panorâmica da cidade.",
      image: "public/panoramic-pizzeria.png",
      rating: 4.2,
      reviewCount: 89,
      priceRange: 2,
      neighborhood: "ibituruna",
      isOpen: true,
      createdAt: "2024-01-20",
    },
    {
      id: 3,
      name: "Bar com Música ao Vivo",
      category: "bar",
      description: "Ambiente descontraído com música ao vivo e drinks especiais.",
      image: "public/bar-musica-ao-vivo.png",
      rating: 4.0,
      reviewCount: 156,
      priceRange: 2,
      neighborhood: "centro",
      isOpen: false,
      createdAt: "2024-01-10",
    },
    {
      id: 4,
      name: "Café Aconchegante",
      category: "cafe",
      description: "Café especial, doces caseiros e ambiente acolhedor.",
      image: "public/cozy-italian-restaurant.png",
      rating: 4.7,
      reviewCount: 203,
      priceRange: 1,
      neighborhood: "santa-terezinha",
      isOpen: true,
      createdAt: "2024-02-01",
    },
    {
      id: 5,
      name: "Teatro Municipal",
      category: "cultura",
      description: "Espetáculos teatrais, shows e eventos culturais.",
      image: "public/municipal-theater-shows.png",
      rating: 4.3,
      reviewCount: 78,
      priceRange: 2,
      neighborhood: "centro",
      isOpen: true,
      createdAt: "2024-01-25",
    },
    {
      id: 6,
      name: "Casa de Shows Eletrônica",
      category: "cultura",
      description: "Música eletrônica, DJs renomados e pista de dança.",
      image: "public/electronic-music-venue.png",
      rating: 3.9,
      reviewCount: 234,
      priceRange: 3,
      neighborhood: "grã-duquesa",
      isOpen: false,
      createdAt: "2024-02-05",
    },
  ]

  filteredEstablishments = [...establishments]
  renderEstablishments()
  updateResultsCount()
}

function renderEstablishments() {
  const establishmentsList = document.getElementById("establishmentsList")
  const featuredGrid = document.getElementById("featuredGrid")

  // Render main establishments list
  const startIndex = 0
  const endIndex = currentPage * itemsPerPage
  const establishmentsToShow = filteredEstablishments.slice(startIndex, endIndex)

  establishmentsList.innerHTML = establishmentsToShow
    .map((establishment) => createEstablishmentCard(establishment))
    .join("")

  // Render featured establishments (top 3)
  const featuredEstablishments = establishments.sort((a, b) => b.rating - a.rating).slice(0, 3)

  featuredGrid.innerHTML = featuredEstablishments
    .map((establishment) => createEstablishmentCard(establishment))
    .join("")

  // Show/hide load more button
  const loadMoreBtn = document.getElementById("loadMoreBtn")
  if (endIndex >= filteredEstablishments.length) {
    loadMoreBtn.style.display = "none"
  } else {
    loadMoreBtn.style.display = "block"
  }
}

function createEstablishmentCard(establishment) {
  const priceSymbols = "$".repeat(establishment.priceRange)
  const stars = "★".repeat(Math.floor(establishment.rating))

  return `
        <div class="establishment-card" onclick="viewEstablishment(${establishment.id})">
            <img src="${establishment.image}" alt="${establishment.name}" class="establishment-image">
            <div class="establishment-content">
                <div class="establishment-category">${establishment.category}</div>
                <h3 class="establishment-title">${establishment.name}</h3>
                <p class="establishment-description">${establishment.description}</p>
                <div class="establishment-info">
                    <div class="establishment-rating">
                        <span class="rating-stars">${stars}</span>
                        <span class="rating-text">${establishment.rating} (${establishment.reviewCount})</span>
                    </div>
                    <div class="establishment-price">${priceSymbols}</div>
                </div>
                <div class="establishment-meta">
                    <div class="establishment-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${establishment.neighborhood}</span>
                    </div>
                    <div class="establishment-actions">
                        <button class="action-btn" onclick="event.stopPropagation(); toggleFavorite(${establishment.id})" title="Favoritar">
                            <i class="fas fa-heart"></i>
                        </button>
                        <button class="action-btn" onclick="event.stopPropagation(); shareEstablishment(${establishment.id})" title="Compartilhar">
                            <i class="fas fa-share"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `
}

function loadMoreEstablishments() {
  if (isLoading) return

  isLoading = true
  currentPage++

  // Simulate loading delay
  setTimeout(() => {
    renderEstablishments()
    isLoading = false
  }, 500)
}

function updateResultsCount() {
  const resultsCount = document.getElementById("resultsCount")
  const total = filteredEstablishments.length
  const showing = Math.min(currentPage * itemsPerPage, total)

  resultsCount.textContent = `Mostrando ${showing} de ${total} estabelecimentos`
}

function viewEstablishment(id) {
  const establishment = establishments.find((est) => est.id === id)
  if (establishment) {
    showEstablishmentModal(establishment)
  }
}

function showEstablishmentModal(establishment) {
  const modal = document.getElementById("establishmentModal")
  const modalBody = document.getElementById("modalBody")

  modalBody.innerHTML = `
        <div class="establishment-detail">
            <img src="${establishment.image}" alt="${establishment.name}" style="width: 100%; height: 300px; object-fit: cover; border-radius: 1rem; margin-bottom: 2rem;">
            <h2>${establishment.name}</h2>
            <p class="establishment-category">${establishment.category}</p>
            <p>${establishment.description}</p>
            <div class="establishment-info">
                <div class="establishment-rating">
                    <span class="rating-stars">${"★".repeat(Math.floor(establishment.rating))}</span>
                    <span>${establishment.rating} (${establishment.reviewCount} avaliações)</span>
                </div>
                <div class="establishment-price">${"$".repeat(establishment.priceRange)}</div>
            </div>
            <div class="establishment-actions" style="margin-top: 2rem;">
                <button class="btn btn-primary" onclick="toggleFavorite(${establishment.id})">
                    <i class="fas fa-heart"></i> Favoritar
                </button>
                <button class="btn btn-secondary" onclick="shareEstablishment(${establishment.id})">
                    <i class="fas fa-share"></i> Compartilhar
                </button>
            </div>
        </div>
    `

  modal.classList.add("active")
}

function toggleFavorite(id) {
  // Toggle favorite status
  const establishment = establishments.find((est) => est.id === id)
  if (establishment) {
    establishment.isFavorite = !establishment.isFavorite
    showNotification(
      "Favoritos",
      establishment.isFavorite ? "Adicionado aos favoritos!" : "Removido dos favoritos!",
      "success",
    )
  }
}

function shareEstablishment(id) {
  const establishment = establishments.find((est) => est.id === id)
  if (establishment && navigator.share) {
    navigator.share({
      title: establishment.name,
      text: establishment.description,
      url: window.location.href,
    })
  } else {
    // Fallback for browsers without Web Share API
    const url = `${window.location.href}?establishment=${id}`
    navigator.clipboard.writeText(url).then(() => {
      showNotification("Compartilhar", "Link copiado para a área de transferência!", "success")
    })
  }
}

// ===== PROMOTIONS =====
function initializePromotions() {
  loadPromotions()
}

function loadPromotions() {
  const promotionsSlider = document.getElementById("promotionsSlider")

  // Mock promotions data
  const promotions = [
    {
      id: 1,
      title: "50% OFF em Pizzas",
      description: "Todas as pizzas com 50% de desconto nas terças-feiras.",
      establishmentName: "Pizzaria Panorâmica",
      validity: "2024-12-31",
      image: "public/panoramic-pizzeria.png",
    },
    {
      id: 2,
      title: "Happy Hour Especial",
      description: "Drinks com preço especial das 17h às 19h.",
      establishmentName: "Bar com Música ao Vivo",
      validity: "2024-12-31",
      image: "public/bar-musica-ao-vivo.png",
    },
    {
      id: 3,
      title: "Café + Doce por R$ 15",
      description: "Combo especial: café expresso + doce caseiro.",
      establishmentName: "Café Aconchegante",
      validity: "2024-12-31",
      image: "public/cozy-italian-restaurant.png",
    },
  ]

  promotionsSlider.innerHTML = promotions
    .map(
      (promotion) => `
        <div class="promotion-card">
            <div class="promotion-badge">OFERTA</div>
            <img src="${promotion.image}" alt="${promotion.title}" class="promotion-image">
            <div class="promotion-content">
                <h4>${promotion.title}</h4>
                <p>${promotion.description}</p>
                <div class="promotion-meta">
                    <span class="establishment-name">${promotion.establishmentName}</span>
                    <span class="promotion-validity">Válido até ${new Date(promotion.validity).toLocaleDateString()}</span>
                </div>
            </div>
        </div>
    `,
    )
    .join("")
}

// ===== EVENTS =====
function initializeEvents() {
  loadEvents()
}

function loadEvents() {
  const eventsSlider = document.getElementById("eventsSlider")

  // Mock events data
  const events = [
    {
      id: 1,
      title: "Show de Rock Nacional",
      description: "Apresentação de bandas locais de rock nacional.",
      date: "2024-03-15",
      time: "20:00",
      venue: "Casa de Shows Eletrônica",
      image: "public/electronic-music-venue.png",
    },
    {
      id: 2,
      title: "Peça Teatral: O Auto da Compadecida",
      description: "Clássico da literatura brasileira em cartaz.",
      date: "2024-03-20",
      time: "19:30",
      venue: "Teatro Municipal",
      image: "public/municipal-theater-shows.png",
    },
    {
      id: 3,
      title: "Noite de Jazz",
      description: "Música jazz ao vivo com artistas renomados.",
      date: "2024-03-25",
      time: "21:00",
      venue: "Bar com Música ao Vivo",
      image: "public/bar-musica-ao-vivo.png",
    },
  ]

  eventsSlider.innerHTML = events
    .map((event) => {
      const eventDate = new Date(event.date)
      const day = eventDate.getDate()
      const month = eventDate.toLocaleDateString("pt-BR", { month: "short" })

      return `
            <div class="event-card">
                <div class="event-date">
                    <span class="day">${day}</span>
                    <span class="month">${month}</span>
                </div>
                <img src="${event.image}" alt="${event.title}" class="event-image">
                <div class="event-content">
                    <h4>${event.title}</h4>
                    <p>${event.description}</p>
                    <div class="event-meta">
                        <span class="establishment-name">${event.venue}</span>
                        <span class="event-time">${event.time}</span>
                    </div>
                </div>
            </div>
        `
    })
    .join("")
}

// ===== NEWSLETTER =====
function initializeNewsletter() {
  const newsletterForm = document.getElementById("newsletterForm")

  newsletterForm.addEventListener("submit", (e) => {
    e.preventDefault()

    const email = e.target.querySelector('input[type="email"]').value

    if (email) {
      // Simulate API call
      setTimeout(() => {
        showNotification("Newsletter", "Inscrição realizada com sucesso!", "success")
        e.target.reset()
      }, 1000)
    }
  })
}

// ===== THEME TOGGLE =====
function initializeTheme() {
  const savedTheme = localStorage.getItem("theme") || "light"
  document.documentElement.setAttribute("data-theme", savedTheme)

  themeToggle.addEventListener("click", () => {
    const currentTheme = document.documentElement.getAttribute("data-theme")
    const newTheme = currentTheme === "dark" ? "light" : "dark"

    document.documentElement.setAttribute("data-theme", newTheme)
    localStorage.setItem("theme", newTheme)

    // Update icon
    const icon = themeToggle.querySelector("i")
    icon.className = newTheme === "dark" ? "fas fa-sun" : "fas fa-moon"
  })
}

// ===== SCROLL EFFECTS =====
function initializeScrollEffects() {
  // Back to top button
  window.addEventListener("scroll", () => {
    if (window.scrollY > 300) {
      backToTop.classList.add("visible")
    } else {
      backToTop.classList.remove("visible")
    }
  })

  backToTop.addEventListener("click", () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    })
  })

  // Intersection Observer for animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("fade-in")
      }
    })
  }, observerOptions)

  // Observe elements for animation
  document.querySelectorAll(".establishment-card, .section-header, .promotion-card, .event-card").forEach((el) => {
    observer.observe(el)
  })
}

// ===== AUTHENTICATION =====
function initializeAuth() {
  // Check if user is logged in
  const user = localStorage.getItem("user")

  if (user) {
    showUserMenu(JSON.parse(user))
  }
}

function showUserMenu(user) {
  const authButtons = document.getElementById("authButtons")
  const userMenu = document.getElementById("userMenu")
  const userName = document.getElementById("userName")
  const userEmail = document.getElementById("userEmail")

  authButtons.style.display = "none"
  userMenu.style.display = "block"
  userName.textContent = user.name
  userEmail.textContent = user.email
}

// ===== MODAL FUNCTIONALITY =====
function initializeModals() {
  const modal = document.getElementById("establishmentModal")
  const modalClose = document.getElementById("modalClose")
  const modalOverlay = modal.querySelector(".modal-overlay")

  modalClose.addEventListener("click", closeModal)
  modalOverlay.addEventListener("click", closeModal)

  // Close modal with Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("active")) {
      closeModal()
    }
  })
}

function closeModal() {
  const modal = document.getElementById("establishmentModal")
  modal.classList.remove("active")
}

// ===== NOTIFICATIONS =====
function showNotification(title, message, type = "info") {
  const container = document.getElementById("notificationContainer")

  const notification = document.createElement("div")
  notification.className = `notification ${type}`

  notification.innerHTML = `
        <div class="notification-header">
            <span class="notification-title">${title}</span>
            <button class="notification-close">&times;</button>
        </div>
        <div class="notification-message">${message}</div>
    `

  container.appendChild(notification)

  // Show notification
  setTimeout(() => {
    notification.classList.add("show")
  }, 100)

  // Auto remove after 5 seconds
  setTimeout(() => {
    removeNotification(notification)
  }, 5000)

  // Close button
  notification.querySelector(".notification-close").addEventListener("click", () => {
    removeNotification(notification)
  })
}

function removeNotification(notification) {
  notification.classList.remove("show")
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification)
    }
  }, 300)
}

// ===== UTILITY FUNCTIONS =====
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

function throttle(func, limit) {
  let inThrottle
  return function () {
    const args = arguments
    
    if (!inThrottle) {
      func.apply(this, args)
      inThrottle = true
      setTimeout(() => (inThrottle = false), limit)
    }
  }
}

// ===== ERROR HANDLING =====
window.addEventListener("error", (e) => {
  console.error("JavaScript Error:", e.error)
  showNotification("Erro", "Ocorreu um erro inesperado. Tente recarregar a página.", "error")
})

// ===== PERFORMANCE OPTIMIZATION =====
// Lazy loading for images
function initializeLazyLoading() {
  const images = document.querySelectorAll("img[data-src]")

  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target
        img.src = img.dataset.src
        img.classList.remove("lazy")
        imageObserver.unobserve(img)
      }
    })
  })

  images.forEach((img) => imageObserver.observe(img))
}

// Initialize modals when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  initializeModals()
  initializeLazyLoading()
})
