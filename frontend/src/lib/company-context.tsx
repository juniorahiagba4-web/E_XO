"use client";

import { createContext, useContext } from "react";
import { DEFAULT_COMPANY, type CompanyInfo } from "./company";

const CompanyContext = createContext<CompanyInfo>(DEFAULT_COMPANY);

/** Hands the company details read on the server to the interactive components. */
export function CompanyProvider({ company, children }: { company: CompanyInfo; children: React.ReactNode }) {
  return <CompanyContext.Provider value={company}>{children}</CompanyContext.Provider>;
}

export function useCompany() {
  return useContext(CompanyContext);
}
