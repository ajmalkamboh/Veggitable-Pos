# CODING STANDARDS & QUALITY GUIDELINES

## 1. PHP 8.3 Requirements
- **Strict Typing**: Declare explicit parameter and return types for all functions and methods.
- **Constructor Promotion**: Use constructor property promotion for dependency injection.
- **Enums**: Use TitleCase for Enum keys (e.g., `TenantStatus::Active`, `VoucherType::Journal`).
- **Control Structures**: Always use curly braces for control structures, even for single-line bodies.
- **Pint Formatting**: Run `vendor/bin/pint --format agent` to automatically maintain Laravel Pint standard styling.

## 2. Laravel & Livewire Standards
- **Transactions**: Always wrap multi-table state changes (especially sales, purchases, wastage, and journals) in database transactions (`DB::transaction(...)`).
- **Authorization**: Never trust client-provided IDs. Validate that records belong to the active tenant in every Form Request and Policy.
- **Livewire Components**:
  - Keep Livewire components focused and delegate domain logic to specialized service classes.
  - Wire actions must validate inputs before mutating database state.
  - Use `wire:loading` and `wire:target` for immediate visual feedback on buttons.

## 3. Bilingual & RTL Standards
- Never hardcode user-facing strings in Blade views. Use `__('filename.key')`.
- Keep database keys, slugs, and system identifiers in English.
- Use `app()->getLocale() === 'ur'` or CSS attribute `html[dir="rtl"]` for direction-aware styles.
- Preserve numerical digits and currency ISO standards appropriately across translations.
