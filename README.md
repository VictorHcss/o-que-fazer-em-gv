# O que fazer em GV

Plataforma que reúne os pontos turísticos, restaurantes, eventos, hospedagens e experiências de **Governador Valadares (MG)** em um só lugar, para facilitar a descoberta do que a cidade oferece.

## Funcionalidades

- **Busca de estabelecimentos** por categoria e bairro, com página de detalhes.
- **Eventos** e **promoções** da cidade.
- **Contas de usuário:** favoritos, avaliações, perfil e configurações de privacidade.
- **Painel administrativo:** estabelecimentos, categorias, avaliações, usuários, relatórios e configurações.
- **API interna em PHP** para busca, favoritos, avaliações e destaques.
- **PWA:** service worker para melhor experiência no celular.

## Tecnologias

PHP · MySQL · PDO · HTML · CSS · JavaScript

## Estrutura

```text
o-que-fazer-em-gv/
├── admin/     # Painel administrativo
├── api/       # Endpoints usados pelo front-end
├── auth/      # Login, cadastro e logout
├── config/    # Conexão com o banco
├── scripts/   # Scripts SQL de criação do banco
└── *.php      # Páginas públicas e área do usuário
```

## Como executar

1. Tenha PHP 8 e MySQL instalados (XAMPP ou similar funciona).
2. Crie um banco chamado `oque_fazer_gv` e execute `scripts/complete-database-setup.sql`.
3. Ajuste a conexão em `config/database.php`, se necessário.
4. Coloque a pasta no servidor local e acesse pelo navegador.

---

Projeto desenvolvido por [Victor Henrique](https://github.com/VictorHcss), de Governador Valadares.
