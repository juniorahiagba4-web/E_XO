import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { COMPANY_ADDRESS, COMPANY_NAME, COMPANY_PHONE, SOCIAL_LINKS } from "@/lib/config";
import { FacebookIcon, InstagramIcon, TiktokIcon, WhatsappIcon } from "@/components/icons/SocialIcons";
import Logo from "./Logo";

export default function Footer() {
  const t = useTranslations("footer");
  const tNav = useTranslations("nav");
  const year = new Date().getFullYear();

  const socials = [
    { href: SOCIAL_LINKS.facebook, label: "Facebook", Icon: FacebookIcon },
    { href: SOCIAL_LINKS.instagram, label: "Instagram", Icon: InstagramIcon },
    { href: SOCIAL_LINKS.tiktok, label: "TikTok", Icon: TiktokIcon },
    { href: SOCIAL_LINKS.whatsapp, label: "WhatsApp", Icon: WhatsappIcon },
  ].filter((social): social is typeof social & { href: string } => Boolean(social.href));

  return (
    <footer className="bg-brand-navy">
      <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div className="flex flex-col gap-3">
          <Link href="/">
            <Logo />
          </Link>
          <p className="text-sm text-slate-300">{t("slogan")}</p>
          <p className="text-sm text-slate-300">{COMPANY_ADDRESS}</p>
          <p className="text-sm text-slate-300">{COMPANY_PHONE}</p>
        </div>

        <div className="flex flex-col gap-2">
          <h3 className="text-lg font-semibold text-brand-gold">{t("sitemapTitle")}</h3>
          <Link href="/" className="text-sm text-slate-300 hover:text-brand-gold">
            {tNav("home")}
          </Link>
          <Link href="/catalogue" className="text-sm text-slate-300 hover:text-brand-gold">
            {tNav("catalogue")}
          </Link>
          <Link href="/contact" className="text-sm text-slate-300 hover:text-brand-gold">
            {tNav("contact")}
          </Link>
          <Link href="/faq" className="text-sm text-slate-300 hover:text-brand-gold">
            {tNav("faq")}
          </Link>
          <Link href="/suivi" className="text-sm text-slate-300 hover:text-brand-gold">
            {tNav("trackQuote")}
          </Link>
        </div>

        <div className="flex flex-col gap-2">
          <h3 className="text-lg font-semibold text-brand-gold">{t("legalTitle")}</h3>
          <Link href="/confidentialite" className="text-sm text-slate-300 hover:text-brand-gold">
            {t("privacyPolicy")}
          </Link>
          <Link href="/conditions" className="text-sm text-slate-300 hover:text-brand-gold">
            {t("termsOfService")}
          </Link>
        </div>

        <div className="flex flex-col gap-3">
          <h3 className="text-lg font-semibold text-brand-gold">{t("followUsTitle")}</h3>
          <div className="flex items-center gap-3">
            {socials.map(({ href, label, Icon }) => (
              <a
                key={label}
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={label}
                className="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 text-slate-200 transition hover:border-brand-gold hover:text-brand-gold"
              >
                <Icon className="h-4 w-4" />
              </a>
            ))}
          </div>
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="mx-auto max-w-6xl px-4 py-6 text-center text-sm text-slate-400">
          <p>© {year} {COMPANY_NAME}. {t("rights")}</p>
        </div>
      </div>
    </footer>
  );
}
