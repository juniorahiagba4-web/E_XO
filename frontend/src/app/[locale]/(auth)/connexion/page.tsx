"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useSearchParams } from "next/navigation";
import { Link, useRouter } from "@/i18n/navigation";
import { useAuth } from "@/lib/auth-context";
import { ApiError } from "@/lib/api";
import AuthLayout from "@/components/AuthLayout";

export default function LoginPage() {
  const t = useTranslations("auth");
  const { login } = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await login({ email, password });
      router.push(searchParams.get("redirect") || "/");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("error"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <AuthLayout
      badge={t("loginBadge")}
      title={t("loginWelcomeTitle")}
      subtitle={t("loginWelcomeSubtitle")}
    >
      <h1 className="mb-2 text-2xl font-bold text-brand-navy">{t("loginTitle")}</h1>
      <p className="mb-8 text-sm text-slate-500">
        {t("noAccount")}{" "}
        <Link href="/inscription" className="font-medium text-brand-navy hover:underline">
          {t("register")}
        </Link>
      </p>

      <form onSubmit={handleSubmit} className="flex flex-col gap-4">
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
          type="password"
          placeholder={t("password")}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
        />

        {error && <p className="text-sm text-red-600">{error}</p>}

        <button
          type="submit"
          disabled={submitting}
          className="rounded-full bg-brand-navy px-6 py-3 font-medium text-white transition hover:bg-brand-navy-light disabled:opacity-50"
        >
          {submitting ? t("submitting") : t("login")}
        </button>
      </form>
    </AuthLayout>
  );
}
