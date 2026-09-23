"use client";

import { useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { sendContactMessage, ApiError } from "@/lib/api";
import { COMPANY_ADDRESS, COMPANY_EMAIL, COMPANY_PHONE, SOCIAL_LINKS } from "@/lib/config";
import { FacebookIcon, InstagramIcon, TiktokIcon, WhatsappIcon } from "@/components/icons/SocialIcons";

function MapPinIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
      <path strokeLinecap="round" strokeLinejoin="round" d="M20 10.5c0 5.5-8 11.5-8 11.5s-8-6-8-11.5a8 8 0 1 1 16 0Z" />
      <circle cx="12" cy="10.5" r="2.5" />
    </svg>
  );
}

function PhoneIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
      <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 4h3.4l1.5 4.5-2 1.5a12 12 0 0 0 6.6 6.6l1.5-2 4.5 1.5v3.4a1.5 1.5 0 0 1-1.6 1.5A17.5 17.5 0 0 1 3 5.6 1.5 1.5 0 0 1 4.5 4Z" />
    </svg>
  );
}

function MailIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path strokeLinecap="round" strokeLinejoin="round" d="m4 7 8 6 8-6" />
    </svg>
  );
}

export default function ContactPage() {
  const t = useTranslations("contact");
  const locale = useLocale();

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [subject, setSubject] = useState("");
  const [message, setMessage] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const socials = [
    { href: SOCIAL_LINKS.facebook, label: "Facebook", Icon: FacebookIcon },
    { href: SOCIAL_LINKS.instagram, label: "Instagram", Icon: InstagramIcon },
    { href: SOCIAL_LINKS.tiktok, label: "TikTok", Icon: TiktokIcon },
    { href: SOCIAL_LINKS.whatsapp, label: "WhatsApp", Icon: WhatsappIcon },
  ].filter((social): social is typeof social & { href: string } => Boolean(social.href));

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await sendContactMessage(locale, {
        name,
        email,
        phone: phone || undefined,
        subject: subject || undefined,
        message,
      });
      setSuccess(true);
      setName("");
      setEmail("");
      setPhone("");
      setSubject("");
      setMessage("");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("error"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mx-auto max-w-5xl px-4 py-12">
      <h1 className="mb-3 text-3xl font-bold text-brand-navy">{t("title")}</h1>
      <p className="mb-10 max-w-2xl text-slate-500">{t("intro")}</p>

      <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <div className="flex flex-col gap-4">
          <h2 className="text-lg font-semibold text-brand-navy">{t("detailsTitle")}</h2>

          <a
            href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(COMPANY_ADDRESS)}`}
            target="_blank"
            rel="noopener noreferrer"
            className="group flex items-start gap-4 rounded-xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-brand-gold hover:bg-brand-gold/5 hover:shadow-md"
          >
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-navy text-brand-gold transition group-hover:scale-110 group-hover:bg-brand-gold group-hover:text-brand-navy">
              <MapPinIcon />
            </span>
            <span>
              <span className="block text-sm font-medium text-brand-navy">{t("addressLabel")}</span>
              <span className="block text-slate-500">{COMPANY_ADDRESS}</span>
            </span>
          </a>

          <a
            href={`tel:${COMPANY_PHONE.replace(/\s+/g, "")}`}
            className="group flex items-start gap-4 rounded-xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-brand-gold hover:bg-brand-gold/5 hover:shadow-md"
          >
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-navy text-brand-gold transition group-hover:scale-110 group-hover:bg-brand-gold group-hover:text-brand-navy">
              <PhoneIcon />
            </span>
            <span>
              <span className="block text-sm font-medium text-brand-navy">{t("phoneLabel")}</span>
              <span className="block text-slate-500">{COMPANY_PHONE}</span>
            </span>
          </a>

          <a
            href={`mailto:${COMPANY_EMAIL}`}
            className="group flex items-start gap-4 rounded-xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-brand-gold hover:bg-brand-gold/5 hover:shadow-md"
          >
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-navy text-brand-gold transition group-hover:scale-110 group-hover:bg-brand-gold group-hover:text-brand-navy">
              <MailIcon />
            </span>
            <span>
              <span className="block text-sm font-medium text-brand-navy">{t("emailLabel")}</span>
              <span className="block text-slate-500">{COMPANY_EMAIL}</span>
            </span>
          </a>

          <div className="rounded-xl border border-slate-200 p-4">
            <p className="mb-3 text-sm font-medium text-brand-navy">{t("socialLabel")}</p>
            <div className="flex items-center gap-3">
              {socials.map(({ href, label, Icon }) => (
                <a
                  key={label}
                  href={href}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label={label}
                  className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:scale-110 hover:border-brand-gold hover:bg-brand-navy hover:text-brand-gold"
                >
                  <Icon className="h-4 w-4" />
                </a>
              ))}
            </div>
          </div>
        </div>

        <div className="rounded-2xl border border-slate-200 p-6">
          <h2 className="mb-6 text-lg font-semibold text-brand-navy">{t("formTitle")}</h2>

          {success ? (
            <p className="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
              {t("success")}
            </p>
          ) : (
            <form onSubmit={handleSubmit} className="flex flex-col gap-4">
              <input
                required
                placeholder={t("name")}
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2"
              />
              <input
                required
                type="email"
                placeholder={t("email")}
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2"
              />
              <input
                placeholder={t("phone")}
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2"
              />
              <input
                placeholder={t("subject")}
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2"
              />
              <textarea
                required
                placeholder={t("message")}
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                rows={5}
                className="rounded-lg border border-slate-300 px-3 py-2"
              />

              {error && <p className="text-sm text-red-600">{error}</p>}

              <button
                type="submit"
                disabled={submitting}
                className="rounded-full bg-brand-navy px-6 py-3 font-medium text-white transition hover:bg-brand-navy-light disabled:opacity-50"
              >
                {submitting ? t("submitting") : t("submit")}
              </button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
