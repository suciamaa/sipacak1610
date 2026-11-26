# SIPACAK – UI/UX Follow-up (10 November 2026)

## Login and account recovery
- Reworked the login/forgot-password footer spacing so the copyright and protected-access note no longer collide.
- Centered the BPS + SIPACAK mobile brand lockup, with the product description and official BPS office line centered underneath.
- Changed the forgot-password heading to a single consistent text color.
- Updated the stylesheet cache key so browsers load the latest CSS.

## Petugas dashboard
- Centered the assignment-count summary on mobile screens.
- Reduced the frozen widths of the first two assignment columns so the Role and following columns remain visible while horizontally scrolling.
- Aligned the assignment table's first text column with the card heading gutter.

## Progress overview and history
- Highlighted the assignment name, district, and role in a dedicated identity panel.
- Rebuilt the history table with the columns Hari, Tanggal, Jam Mulai, Jam Selesai, Desa, SLS/Blok Sensus, and Aksi.
- Rendered each location detail on its own row. Edit/Delete remain grouped by progress date because those actions operate on the complete daily record.
- Aligned the history table to the card heading gutter and kept Hari/Tanggal frozen on mobile.

## Progress create/edit form
- Highlighted the activity, district, and role at the top of the form.
- Removed the separate Desa/Kelurahan type selector; Desa and Kelurahan are selected from the same dropdown.
- Retained the Jenis Data Wilayah selector for SLS versus Blok Sensus.
- Hide the Sub-SLS field unless the selected SLS has Sub-SLS records.
- Reorganized Data Perjalanan into a two-column field layout.
- Placed Menemui Penguasa Wilayah beside Aktivitas yang Dilakukan, and Permasalahan beside Pemecahan Masalah; these groups stack on mobile.
- Renamed SLS-specific detail actions to Lokasi so the same form terminology also fits Blok Sensus entries.

## Validation
- PHP lint passed for changed PHP/Blade source files.
- All 36 Blade templates compiled and the generated PHP passed syntax checks.
- The progress-form JavaScript passed `node --check`.
- The active stylesheet parsed without CSS syntax errors.
- Browser/mobile visual testing and live database submission still need to be checked in the user's local XAMPP/Laravel environment.
