# TokenKeep 🗝️

TokenKeep is a ultra-lightweight, self-hosted PHP & SQLite microservice designed to safely run the OAuth2 Authorization Code Flow once per external provider (e.g., Zoho, Pipedrive, Google) and act as an internal gateway to fetch valid, auto-refreshing access tokens via simple HTTP API.

---

## Architecture Flow

```mermaid
sequenceDiagram
    autonumber
    actor Dev as Developer / App
    participant TK as TokenKeep Service
    participant SQLite as SQLite DB
    participant OAuth as External Provider (API)

    Note over Dev, OAuth: 1. Setup Phase (Once)
    Dev->>TK: Register provider in DB (via CLI or SQL)
    Dev->>TK: Direct browser to /auth.php?provider=zoho
    TK-->>Dev: Redirect to Provider Auth Page
    Dev->>OAuth: Authorize Application
    OAuth-->>TK: Callback to /callback.php with ?code=XYZ
    TK->>OAuth: Exchanges code for Tokens (POST)
    OAuth-->>TK: Returns Access & Refresh tokens
    TK->>SQLite: Saves tokens with expires_at timestamp

    Note over Dev, OAuth: 2. Operation Phase (Token request)
    Dev->>TK: GET /api/token.php?provider=zoho with Bearer Key
    alt Token is valid (> 5 minutes left)
        TK->>SQLite: Reads access_token
        SQLite-->>TK: Token details
        TK-->>Dev: Returns {"access_token": "..."}
    else Token is expired (or close to it)
        TK->>SQLite: Reads refresh_token
        TK->>OAuth: Request new access_token using refresh_token
        OAuth-->>TK: Returns new Access & Refresh tokens
        TK->>SQLite: Updates tokens in Database
        TK-->>Dev: Returns refreshed {"access_token": "..."}
    end

## Features
- **Zero Heavy Dependencies**: Pure PHP 8.2+, no frameworks.
- **SQLite Database**: Maximum portability with a single `.sqlite` file.
- **Secure by Design**: Core files, SQLite database, and configuration are placed outside the web-accessible `public` root folder.
- **Lazy-Refresh**: Checks token freshness and auto-refreshes on the fly before delivery.
- **CLI Cron Utility**: Optional cron job scripts to force-refresh tokens in the background.

---

## Requirements
- PHP 8.2 or higher
- PDO SQLite extension (`pdo_sqlite`)
- PHP cURL extension

---

## Quick Start (Manual Hosting)

1. Clone or download the repository.
2. Duplicate the default configuration:
   ```bash
   cp config.example.php config.php
   ```
3. Open `config.php` and set up your `api_secret_token` with a strong random string.
4. Register your first OAuth Provider using the CLI helper:
   ```bash
   php register_provider.php
   ```
5. Point your web server's document root to `/public`, or start the built-in PHP server:
   ```bash
   php -S localhost:8000 -t public/
   ```

---

## Quick Start (Docker Setup)

You can launch TokenKeep in a single command using Docker:

```bash
cp config.example.php config.php
docker-compose up -d --build
```

The application will be accessible at `http://localhost:8000`.

---

## API Documentation

### 1. Initiate OAuth Flow
Direct your browser to:
```http
GET http://localhost:8000/auth.php?provider=your_provider_alias
```
*TokenKeep will automatically generate secure CSRF states and redirect your browser to the external provider's login screen.*

### 2. Retrieve Valid Access Token
Make an internal API request from your backend:
```bash
curl -X GET "http://localhost:8000/api/token.php?provider=your_provider_alias" \
     -H "Authorization: Bearer YOUR_SUPER_SECRET_TOKEN_KEEP_KEY_HERE"
```

**Successful Response:**
```json
{
  "access_token": "your_valid_oauth2_access_token_here"
}
```

---

## Cron Refresh Setup
To ensure tokens never expire even without live traffic requests, register a system cron job to run the script every 15 minutes:

```cron
*/15 * * * * /usr/bin/php /path/to/tokenkeep/cron_refresh.php > /dev/null 2>&1
```

## License
This project is open-source and available under the [MIT License](LICENSE).
