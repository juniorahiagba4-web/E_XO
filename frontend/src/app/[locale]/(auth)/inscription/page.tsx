"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useSearchParams } from "next/navigation";
import { Link, useRouter } from "@/i18n/navigation";
import { useAuth } from "@/lib/auth-context";
import { ApiError } from "@/lib/api";
import AuthLayout from "@/components/AuthLayout";

export default function RegisterPage() {
  const t = useTranslations("auth");
  const { register } = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();

  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await register({
        first_name: firstName,
        last_name: lastName,
        email,
        phone,
        password,
        password_confirmation: passwordConfirmation,
      });
      router.push(searchParams.get("redirect") || "/");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("error"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <AuthLayout
      badge={t("registerBadge")}
      title={t("registerWelcomeTitle")}
      subtitle={t("registerWelcomeSubtitle")}
    >
      <h1 className="mb-2 text-2xl font-bold text-brand-navy">{t("registerTitle")}</h1>
      <p className="mb-8 text-sm text-slate-500">
        {t("hasAccount")}{" "}
        <Link href="/connexion" className="font-medium text-brand-navy hover:underline">
          {t("login")}
        </Link>
      </p>

      <form onSubmit={handleSubmit} className="flex flex-col gap-4">
        <div className="grid grid-cols-2 gap-4">
          <input
            required
            placeholder={t("firstName")}
            value={firstName}
            onChange={(e) => setFirstName(e.target.value)}
            className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
          />
          <input
            required
            placeholder={t("lastName")}
            value={lastName}
            onChange={(e) => setLastName(e.target.value)}
            className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
          />
        </div>
        <input
          required
          type="email"
          placeholder={t("email")}
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
        />
        <input
          required
          placeholder={t("phone")}
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
          className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
        />
        <input
          required
          type="password"
          placeholder={t("password")}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
        />
        <input
          required
          type="password"
          placeholder={t("passwordConfirmation")}
          value={passwordConfirmation}
          onChange={(e) => setPasswordConfirmation(e.target.value)}
          className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
        />

        {error && <p className="text-sm text-red-600">{error}</p>}

        <button
          type="submit"
          disabled={submitting}
          className="rounded-full bg-brand-navy px-6 py-3 font-medium text-white transition hover:bg-brand-navy-light disabled:opacity-50"
        >
          {submitting ? t("submitting") : t("register")}
        </button>
      </form>
    </AuthLayout>
  );
}
