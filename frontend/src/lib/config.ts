export const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

// Numéro de test (Wilson) le temps de valider le parcours en conditions
// réelles — à remplacer par le numéro définitif de l'entreprise avant la mise en ligne.
export const WHATSAPP_NUMBER = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER ?? "22893040103";

export function buildWhatsappLink(message: string) {
  const encoded = encodeURIComponent(message);
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encoded}`;
}

export const COMPANY_NAME = "La Perle d'Or";

export const COMPANY_SLOGAN_FR = "Le mobilier événementiel, sans le tracas.";

export const COMPANY_SLOGAN_EN = "Event furniture, without the hassle.";

export const COMPANY_ADDRESS =
  process.env.NEXT_PUBLIC_COMPANY_ADDRESS ?? "Cité Millénium, face restaurant Le Beffcut, Lomé, Togo";

// Même numéro de test que WHATSAPP_NUMBER ci-dessus pour les appels directs.
export const COMPANY_PHONE = process.env.NEXT_PUBLIC_COMPANY_PHONE ?? "+228 93 04 01 03";

export const COMPANY_EMAIL = process.env.NEXT_PUBLIC_COMPANY_EMAIL ?? "contact@laperledor.tg";

// Seul le compte TikTok est actif pour le moment — Facebook et Instagram
// restent à `null` (masqués dans le footer) tant que ces comptes n'existent
// pas réellement, plutôt que de pointer vers un lien inventé.
// TODO: confirmer le vrai handle TikTok (celui ci-dessous est un placeholder).
export const SOCIAL_LINKS: {
  facebook: string | null;
  instagram: string | null;
  tiktok: string | null;
  whatsapp: string;
} = {
  facebook: process.env.NEXT_PUBLIC_FACEBOOK_URL ?? null,
  instagram: process.env.NEXT_PUBLIC_INSTAGRAM_URL ?? null,
  tiktok: process.env.NEXT_PUBLIC_TIKTOK_URL ?? "https://www.tiktok.com/@laperledor.tg",
  whatsapp: `https://wa.me/${WHATSAPP_NUMBER}`,
};
