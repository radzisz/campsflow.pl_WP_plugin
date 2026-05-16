# Campsflow WP Plugin

WordPress plugin integrujący WordPressa z systemem rezerwacji [Campsflow.pl](https://campsflow.pl).

## Co robi plugin

1. **Synchronizuje** imprezy i turnusy z Campsflow API do lokalnych Custom Post Types (CPT) — raz na godzinę (konfigurowalne). Pełne SEO: permalinki, sitemapy, Yoast/RankMath działają natywnie.

2. **Wyświetla** ofertę przez shortcodes lub Elementor widgets. Dwa tryby: lista imprez (z wyborem turnusu) lub płaska lista turnusów (dla małych tenantów).

3. **Osadza** formularz rejestracji Campsflow jako iframe na dedykowanej stronie WP — firma zachowuje własny nagłówek i stopkę.

## Wymagania

- Docker Desktop
- Node.js 18+

PHP i Composer działają przez Docker — nic nie instalujesz lokalnie.

## Dev environment

```bash
npm install
npm run composer:install
npm run env:start
```

WordPress: http://localhost:8888 · admin / password

## Testy

```bash
npm run test:unit          # izolowana logika (Brain\Monkey, bez WP)
npm run test:integration   # pełne WP (wymaga env:start)
npm run analyse            # PHPStan level 8
npm run lint               # WordPress Coding Standards
```

## Użycie (shortcodes)

```
[campsflow_listing view="events"]
[campsflow_listing view="sessions"]
[campsflow_register_button session_id="uuid" label="Zapisz się"]
[campsflow_registration_form]
```

Szczegóły architektoniczne: [CLAUDE.md](CLAUDE.md)
