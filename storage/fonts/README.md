# PDF Certificate Fonts

This directory contains fonts used for PDF certificate generation.

## Required Fonts

For Thai language support in certificates, download the following fonts:

### Sarabun Font (Recommended for Thai)
- Download from: https://fonts.google.com/specimen/Sarabun
- Download all weights: Light (300), Regular (400), SemiBold (600), Bold (700)
- Place TTF files in this directory:
  - `Sarabun-Light.ttf`
  - `Sarabun-Regular.ttf`
  - `Sarabun-SemiBold.ttf`
  - `Sarabun-Bold.ttf`

### TH Sarabun New (Alternative)
- Official Thai government font
- Download from Thai government font repository
- Files: `THSarabunNew.ttf`, `THSarabunNew-Bold.ttf`

## Installation Instructions

1. Download the font files from the sources above
2. Place them in this directory (`storage/fonts/`)
3. DOMPDF will automatically detect and use these fonts
4. No additional configuration needed

## Notes

- The certificate templates use `Sarabun` as the primary font
- If fonts are not found locally, DOMPDF will attempt to use system fonts
- For best results in production, install local fonts
- Make sure the storage/fonts directory is writable
