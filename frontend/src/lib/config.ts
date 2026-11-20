export const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

// "staging" on the pre-production copy (test.laperledor-togo.com), set in
// Vercel for the "staging" branch only.
export const IS_STAGING = process.env.NEXT_PUBLIC_SITE_ENV === "staging";

export const COMPANY_NAME = "La Perle d'Or";

export const COMPANY_SLOGAN_FR = "Le mobilier événementiel, sans le tracas.";

export const COMPANY_SLOGAN_EN = "Event furniture, without the hassle.";

// Fiche Google Maps de la boutique : le lien court l'ouvre (dans l'app Maps
// sur téléphone), la requête centre l'aperçu de carte de la page Contact dessus.
export const COMPANY_MAPS_URL = "https://maps.app.goo.gl/SFeSmWYsiHrr7wYE9";
export const COMPANY_MAPS_QUERY = "La Perle d’Or, Av. Akeï, Lomé";

// Téléphones, WhatsApp, e-mail, adresse et réseaux sociaux : modifiables dans
// l'admin (Paramètres > Coordonnées de l'entreprise), voir src/lib/company.ts.
