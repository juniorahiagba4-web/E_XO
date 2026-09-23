import createMiddleware from "next-intl/middleware";
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { routing } from "./i18n/routing";

const intlMiddleware = createMiddleware(routing);

// Derive the backend origin from NEXT_PUBLIC_API_URL so the CSP can allow
// fetches/images from it without hard-coding an environment-specific host.
function backendOrigin(): string {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";
  try {
    return new URL(apiUrl).origin;
  } catch {
    return "";
  }
}

export function proxy(request: NextRequest) {
  const response = intlMiddleware(request) ?? NextResponse.next();
  const extraHost = backendOrigin();

  response.headers.set("X-Content-Type-Options", "nosniff");
  response.headers.set("X-Frame-Options", "DENY");
  response.headers.set("Referrer-Policy", "strict-origin-when-cross-origin");
  response.headers.set("Permissions-Policy", "camera=(), microphone=(), geolocation=()");
  response.headers.set(
    "Content-Security-Policy",
    [
      "default-src 'self'",
      // Next.js injects inline bootstrap scripts, so 'unsafe-inline'/'unsafe-eval'
      // are kept for both dev and production bundles.
      "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
      "style-src 'self' 'unsafe-inline'",
      `img-src 'self' data: blob: ${extraHost}`.trim(),
      "font-src 'self' data:",
      `connect-src 'self' ${extraHost}`.trim(),
    ].join("; ") + ";",
  );

  if (request.nextUrl.protocol === "https:") {
    response.headers.set("Strict-Transport-Security", "max-age=31536000; includeSubDomains");
  }

  return response;
}

export const config = {
  matcher: ["/((?!api|trpc|_next|_vercel|.*\\..*).*)"],
};
