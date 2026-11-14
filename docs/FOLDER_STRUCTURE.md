# FOLDER STRUCTURE & MODULAR CODE ORGANIZATION

```
app/
├── Core/                      # Cross-cutting platform concerns
│   ├── MultiTenancy/          # Tenant resolution, manager, scopes, traits
│   ├── Accounting/            # Double-entry posting engine, voucher types
│   ├── Inventory/             # FIFO calculation engine, batch tracking
│   └── Localization/          # Direction helpers, RTL formatter
├── Enums/                     # PHP 8.3 Enums (VoucherType, TenantStatus, UnitType)
├── Http/
│   ├── Controllers/           # Standard web & API controllers
│   ├── Middleware/            # SetLocale, TenantMiddleware, CheckSubscription
│   └── Requests/              # Dedicated FormRequests per feature
├── Livewire/                  # Interactive Livewire components
│   ├── Pos/                   # Vegetable POS counter components
│   ├── Products/              # Product and conversion components
│   ├── Purchases/             # Purchase entry and invoice components
│   ├── Stock/                 # Inventory adjustment and wastage components
│   └── Admin/                 # Super admin management components
├── Models/                    # Eloquent models with TenantScoped traits
├── Policies/                  # Tenant and role authorization policies
├── Services/                  # Business logic services (StockService, JournalService)
└── Traits/                    # Reusable Eloquent traits (BelongsToTenant, Auditable)

resources/
├── css/                       # Custom styles
├── js/                        # Custom scripts
└── views/
    ├── components/            # Reusable Blade UI components (stat-card, card, modal)
    ├── layouts/
    │   ├── admin.blade.php    # EasyAdmin Pro master layout (LTR & RTL)
    │   ├── auth.blade.php     # Authentication and onboarding layout
    │   └── pos.blade.php      # High-speed POS full-screen layout
    ├── partials/              # Layout partials (header, sidebar, footer)
    └── welcome.blade.php      # Technical architecture dashboard

lang/
├── en/                        # English translations (common, pos, finance, etc.)
└── ur/                        # Urdu translations with Nastaliq shaping

public/
└── assets/                    # EasyAdmin Pro vendor and CSS/JS assets
    ├── css/                   # main.css, custom-rtl.css
    ├── js/                    # main.js, theme.js
    └── vendor/                # bootstrap, icons, simple-datatables, etc.
```

