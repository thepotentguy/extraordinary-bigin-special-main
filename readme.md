# Extraordinary Specials 2.0

The plugin turns a WordPress special into a measurable direct-booking journey:

1. A visitor opens an offer.
2. The plugin records a GA4 `view_promotion` event.
3. **Book this offer** opens the correct eRes property page with the offer promo code already loaded through `#SearchResult:PromoCode=CODE`.
4. **Enquire now** keeps the visitor on the page and sends structured offer, source and campaign context into the existing Bigin form.
5. Bigin can use those fields for ownership, follow-up tasks, reporting and remarketing.

Nebula/eRes remains the authority for live availability and the final rate. Bigin holds the sellable catalogue, enquiry journey and reservation follow-up.

## Version 2.0 changes

- Adds eRes promo code, product code and booking URL to every offer.
- Preserves all legacy offer metadata and gallery records.
- Detects promo codes and eRes links in old offer copy when the new fields are blank.
- Stops marking already-ended offers as “Ending soon.”
- Disables booking calls to action on ended offers and routes visitors to an enquiry about current offers.
- Sends `view_promotion`, `select_promotion` and `generate_lead` events to the existing `dataLayer` for GA4/GTM.
- Keeps first-touch UTM values for the browser session.
- Populates configurable hidden fields in the existing Bigin form.
- Loads public assets only on offer pages and the offer archive.
- Removes the third-party Swiper dependency and fixes unsafe output and invalid PHP short tags.
- Repairs the WordPress gallery editor.

## Configure each website

Open **Settings → Extraordinary Specials** and set:

- Property name.
- Site-wide eRes booking URL.
- Accent colour and ending-soon window.
- Existing Bigin embed code.
- Bigin form field API names.

Version 2.0 ships with the API names created in the live Extraordinary Enquiries pipeline on 16 September 2026:

| Purpose | Default Bigin API name |
|---|---|
| Property | `POTENTIALCF3` |
| Referring website / full page URL | `POTENTIALCF1` |
| Offer name | `POTENTIALCF6` |
| Offer code | `POTENTIALCF7` |
| Promo code | `POTENTIALCF8` |
| UTM medium | `POTENTIALCF9` |
| Lead channel | `POTENTIALCF10` |
| UTM source | `POTENTIALCF11` |
| UTM campaign | `POTENTIALCF12` |
| Submission ID | `POTENTIALCF13` |
| Meta click ID | `POTENTIALCF14` |
| Google click ID | `POTENTIALCF15` |
| Landing URL | `POTENTIALCF16` |

The form also contains visible booking fields for Checkout Date (`POTENTIALCF54`) and Number of Rooms (`POTENTIALCF55`). The automated journey fields above are populated and hidden by the plugin. Settings remain editable if Bigin assigns different API names in another account.

## Prepare an offer

In **Exclusive Offers**, enter:

- A real validity date.
- Display price and package summary.
- The eRes promo code.
- A stable product code, for example `HAM-SUMMER-2026`.
- An offer-specific eRes booking URL only when it differs from the website default.

Use the same product code in Bigin Products, website reporting and the WhatsApp catalogue. Do not publish an offer until its destination property, copy, price, validity and promo code have been checked against eRes.

## Safe rollout

1. Install version 2.0 on a staging site or one pilot property.
2. Save the settings and update one current offer.
3. Open the offer with test UTMs.
4. Confirm that **Book this offer** preloads the promo code in eRes.
5. Submit one clearly labelled test enquiry and confirm its structured fields in Bigin.
6. Confirm the three events in GA4 DebugView.
7. Roll out to the remaining property sites after the pilot passes.

The plugin does not read, move or change messages in the reservations Outlook mailbox.
