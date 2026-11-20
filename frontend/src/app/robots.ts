import type { MetadataRoute } from "next";
import { IS_STAGING } from "@/lib/config";
import { SITE_URL } from "@/lib/seo";

export default function robots(): MetadataRoute.Robots {
  // The test copy stays out of every search engine.
  if (IS_STAGING) {
    return { rules: { userAgent: "*", disallow: "/" } };
  }

  return {
    rules: {
      userAgent: "*",
      allow: "/",
      // Pages that belong to one visitor (cart, account, order follow-up...).
      disallow: [
        "/*/compte",
        "/*/favoris",
        "/*/devis",
        "/*/suivi",
        "/*/avis/",
        "/*/connexion",
        "/*/inscription",
      ],
    },
    sitemap: `${SITE_URL}/sitemap.xml`,
    host: SITE_URL,
  };
}
