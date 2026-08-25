# Architektura i decyzje projektowe

Ten dokument wyjaśnia, jak zbudowana jest aplikacja i dlaczego — w szczególności
czym poprawia stary system, z którego została odtworzona.

## Warstwy

```
Komponent Livewire/Volt  →  obiekt Form / akcja  →  model Eloquent  →  DB
                         →  Policy (autoryzacja)
                         →  Observer (audit)
```

- **Komponenty pozostają cienkie.** Komponenty single-file Volt obsługują stan UI
  i delegują pracę domenową do klas akcji lub obiektów Form. Żadnego surowego SQL-a
  ani reguł biznesowych w warstwie widoku.
- **Akcje enkapsulują operacje.** Każdy krok obiegu to osobna klasa w
  `App\Actions\Transfers` (`RequestTransfer`, `AcceptTransfer`, `AcceptLiquidation`,
  `RejectRequest`, …). Są testowalne w izolacji i wstrzykiwane do komponentów.
- **Obiekty Form** (`App\Livewire\Forms\AssetForm`) trzymają reguły walidacji oraz
  mapowanie create/update, dzięki czemu komponent jest mały.
- **Query scopes** na `Asset` (`search`, `forField`, `withStatus`) zastępują doraźne
  łańcuchy `where()` i są współdzielone przez listę oraz eksporter.
- **Enumy** (`AssetStatus`, `TransferType`, `TransferStatus`) zastępują magiczne
  stringi i niosą własne polskie etykiety, kolory oraz logikę `isOpen()` obiegu.
- **Autoryzacja** opiera się na politykach (`AssetPolicy`, `TransferRequestPolicy`)
  na bazie ról i uprawnień spatie/laravel-permission.
- **Audyt** obsługuje `AssetObserver`, zapisując dziennik `asset_activities` w trybie
  append-only.

## Maszyna stanów transferu / likwidacji

`TransferStatus`:

```
Transfer:    Pending ──▶ PendingInventory ──▶ Completed
                    └─────────┬───────────────▶ Rejected
Likwidacja:  PendingInventory ──▶ Completed
                    └───────────▶ Rejected
```

Przejścia żyją w klasach akcji; efekty uboczne na środku (przeniesienie do pola
docelowego albo oznaczenie jako zlikwidowany wraz z datą) wykonują się w transakcji
bazodanowej.

**Rozdział obowiązków.** Dwa kroki akceptacji wykonują różne role: krok 1
(`acceptTarget`) jest dozwolony wyłącznie dla **członka pola docelowego** (pivot
`inventory_field_user`) — odbiorcy, który akceptuje przychodzący środek — natomiast
krok 2 (`acceptInventory`) jest zarezerwowany dla **sekcji inwentaryzacji**. Środek
jest też **zablokowany** (brak edycji/usuwania, brak drugiego wniosku), dopóki ma
jakikolwiek otwarty wniosek.

## Co zmieniło się względem starego systemu

| Stary system | Ta reimplementacja | Dlaczego |
|---|---|---|
| tabela `roles` użyta jako „pola spisowe"; magiczne ID ról `999998/999999` | dedykowana tabela `inventory_fields`; nazwane role przez spatie | czytelność, integralność referencyjna, prawdziwe RBAC |
| `zasoby.id` = sklejony string jako PK | PK auto-increment + `unique(inventory_number, inventory_field_id)` | poprawność, prostsze relacje |
| grube kontrolery (800–1100 linii) z surowymi zapytaniami `DB::` | cienkie komponenty + akcje + scopes | testowalność, rozdział odpowiedzialności |
| logika audytu w `boot()` modelu | dedykowany `AssetObserver` | pojedyncza odpowiedzialność |
| migracje bazowe w gitignore; deploy przez kopiowanie bazy | pełne migracje + fabryki + seedery | odtwarzalność od czystego klona |
| specyfika instytucji/LDAP zaszyta w kodzie | usunięta; generyczny auth + opcjonalny import ze starego systemu | przenośność |

## Strategia testów

- **Jednostkowe** — logika enumów i akcji.
- **Feature** — interakcje komponentów Volt (`Livewire::test`), endpointy HTTP,
  macierze autoryzacji (data-driven), importer (na syntetycznej, in-memory bazie
  legacy) oraz odpowiedzi PDF/eksportu.
- Lokalnie wszystko biega na SQLite in-memory dla szybkości; w CI ta sama bramka
  uruchamiana jest na PostgreSQL (silniku produkcyjnym).
