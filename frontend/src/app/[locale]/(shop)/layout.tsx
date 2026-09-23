import Header from "@/components/Header";
import Footer from "@/components/Footer";
import FloatingWhatsapp from "@/components/FloatingWhatsapp";
import { WHATSAPP_NUMBER } from "@/lib/config";

export default function ShopLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <Header />
      <main className="flex-1">{children}</main>
      <Footer />
      <FloatingWhatsapp
        message={`Bonjour, je souhaite avoir des informations sur vos mobiliers (WhatsApp: +${WHATSAPP_NUMBER}).`}
      />
    </>
  );
}
