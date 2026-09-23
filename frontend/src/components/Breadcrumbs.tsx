import { Link } from "@/i18n/navigation";

export type BreadcrumbItem = {
  label: string;
  href?: string;
};

export default function Breadcrumbs({ items }: { items: BreadcrumbItem[] }) {
  return (
    <nav aria-label="Fil d'Ariane" className="mb-4 flex flex-wrap items-center gap-1.5 text-sm text-slate-400">
      {items.map((item, index) => {
        const isLast = index === items.length - 1;
        return (
          <span key={`${item.label}-${index}`} className="flex items-center gap-1.5">
            {index > 0 && <span aria-hidden>/</span>}
            {item.href && !isLast ? (
              <Link href={item.href} className="transition hover:text-brand-gold-dark">
                {item.label}
              </Link>
            ) : (
              <span className={isLast ? "font-medium text-brand-navy" : ""}>{item.label}</span>
            )}
          </span>
        );
      })}
    </nav>
  );
}
