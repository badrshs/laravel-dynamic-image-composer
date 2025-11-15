# Download Default Fonts

Run these commands to download free, open-source fonts:

```bash
cd resources/fonts

# Download Roboto (English)
curl -L "https://github.com/google/roboto/releases/download/v2.138/roboto-unhinted.zip" -o roboto.zip
unzip roboto.zip "Roboto-Regular.ttf"
rm roboto.zip

# Download Noto Kufi Arabic
curl -L "https://github.com/google/fonts/raw/main/ofl/notokufiarabic/NotoKufiArabic%5Bwght%5D.ttf" -o "NotoKufiArabic-Regular.ttf"
```

Or download manually:
- Roboto: https://fonts.google.com/specimen/Roboto
- Noto Kufi Arabic: https://fonts.google.com/specimen/Noto+Kufi+Arabic

Place the .ttf files in `resources/fonts/`
