import i18n from "i18next";
import { initReactI18next } from "react-i18next";
import LanguageDetector from "i18next-browser-languagedetector";

import en from "./locales/en.json";
import ar from "./locales/ar.json";

// Define available translations
const resources = {
  en: { translation: en },
  ar: { translation: ar },
} as const;

i18n
  .use(initReactI18next)
  .use(LanguageDetector) // Auto-detects user language
  .init({
    resources,
    lng: localStorage.getItem("lang") || "en", // Default language
    fallbackLng: "en",
    interpolation: { escapeValue: false },
  });

export default i18n;
