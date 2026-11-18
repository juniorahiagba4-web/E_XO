import Header from "@/components/Header";
import Footer from "@/components/Footer";
import FloatingWhatsapp from "@/components/FloatingWhatsapp";
import BackToTop from "@/components/BackToTop";
import CookieBanner from "@/components/CookieBanner";

export default function ShopLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <Header />
      <main className="flex-1">{children}</main>
      <Footer />
      <FloatingWhatsapp
        message={`Bonjour, je souhaite avoir des informations sur vos mobiliers.`}
      />
      <BackToTop />
      <CookieBanner />
    </>
  );
}
