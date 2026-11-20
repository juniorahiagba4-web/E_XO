import { cache } from "react";
import { API_URL } from "./config";

/**
 * The company's phones, WhatsApp, e-mail, address and social pages. They are
 * edited in the admin ("Paramètres > Coordonnées de l'entreprise") and read
 * from /site-settings; the values below are only used if the API cannot be
 * reached.
 */
export type CompanyInfo = {
  phone: string;
  whatsapp: string;
  /** Digits only, with the country code: what wa.me links need. */
  whatsapp_digits: string;
  other_phones: { label: string; number: string }[];
  email: string | null;
  address: string;
  social: { tiktok: string | null; facebook: string | null; instagram: string | null };
};

export const DEFAULT_COMPANY: CompanyInfo = {
  phone: "+228 93 36 07 70",
  whatsapp: "+228 93 04 01 03",
  whatsapp_digits: "22893040103",
  other_phones: [],
  email: "contact@laperledor-togo.com",
  address: "Cité Millénium, face restaurant Le Beffcut, Lomé, Togo",
  social: { tiktok: "https://www.tiktok.com/@laperledor.togo", facebook: null, instagram: null },
};

/**
 * Read on the server, at most once a minute (a change in the admin shows up
 * within a minute without slowing every page down).
 */
export const getCompany = cache(async (): Promise<CompanyInfo> => {
  try {
    const res = await fetch(`${API_URL}/site-settings`, {
      headers: { Accept: "application/json" },
      next: { revalidate: 60 },
    });
    if (!res.ok) return DEFAULT_COMPANY;
    const body = (await res.json()) as { data?: { company?: Partial<CompanyInfo> } };
    const company = body.data?.company;
    if (!company?.whatsapp_digits || !company.phone) return DEFAULT_COMPANY;
    return {
      ...DEFAULT_COMPANY,
      ...company,
      other_phones: company.other_phones ?? [],
      social: { ...DEFAULT_COMPANY.social, ...company.social },
    } as CompanyInfo;
  } catch {
    return DEFAULT_COMPANY;
  }
});

export function whatsappLink(company: CompanyInfo, message?: string) {
  const base = `https://wa.me/${company.whatsapp_digits}`;
  return message ? `${base}?text=${encodeURIComponent(message)}` : base;
}

export function telHref(number: string) {
  return `tel:${number.replace(/[^\d+]/g, "")}`;
}
