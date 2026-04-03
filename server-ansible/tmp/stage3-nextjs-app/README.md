# Snipe-IT — Next.js + HeroUI App (Stage 3)

Asset management UI built with **Next.js 14** (App Router) and **HeroUI** (formerly NextUI) component library.

## Stack

| Layer      | Technology                            |
|------------|---------------------------------------|
| Framework  | Next.js 14 (App Router, RSC)          |
| UI Library | HeroUI (heroui-org/heroui)            |
| Styling    | Tailwind CSS                          |
| Data       | REST API (configurable base URL)      |
| Language   | TypeScript                            |

## Structure

```
stage3-nextjs-app/
├── src/
│   ├── app/
│   │   ├── layout.tsx          — Root layout with HeroUIProvider + nav
│   │   ├── page.tsx            — Dashboard / home page
│   │   ├── assets/
│   │   │   ├── page.tsx        — Asset list page
│   │   │   ├── [id]/page.tsx   — Asset detail page
│   │   │   └── create/page.tsx — Create asset form
│   │   ├── licenses/
│   │   │   ├── page.tsx        — License list page
│   │   │   └── [id]/page.tsx   — License detail page
│   │   └── users/
│   │       └── page.tsx        — Users list page
│   ├── components/
│   │   ├── AssetTable.tsx      — Sortable/filterable asset table
│   │   ├── LicenseCard.tsx     — License card with seat usage
│   │   ├── CheckoutModal.tsx   — Checkout/checkin modal
│   │   └── StatusBadge.tsx     — Asset status badge
│   ├── lib/
│   │   └── api.ts              — API client (fetch wrappers)
│   └── types/
│       └── index.ts            — TypeScript interfaces
├── package.json
├── tailwind.config.ts
├── tsconfig.json
└── next.config.ts
```

## Quick Start

```bash
npm install
# Configure your API endpoint:
echo "NEXT_PUBLIC_API_URL=http://localhost:8080/api" > .env.local
npm run dev
```

Open [http://localhost:3000](http://localhost:3000).

## Environment Variables

| Variable              | Default                  | Description              |
|-----------------------|--------------------------|--------------------------|
| `NEXT_PUBLIC_API_URL` | `http://localhost:8080/api` | Backend API base URL |

## Features

- Asset list with search, sort, and pagination (HeroUI Table)
- Asset detail view with checkout/checkin modal (HeroUI Modal)
- License list with seat availability chips
- Create asset form with HeroUI Input / Select
- Responsive layout with HeroUI Navbar
- Status badges with color-coded HeroUI Chip
