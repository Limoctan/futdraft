import type { InertiaLinkProps } from "@inertiajs/react";
import { clsx } from "clsx";
import type { ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps["href"]>): string {
    return typeof url === "string" ? url : url.url;
}

export function formatCurrency(cents: number, currency: string) {
    const amount = cents / 100;
    const formatters: Record<string, Intl.NumberFormat> = {
        USD: new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
        }),
        EUR: new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }),
        COP: new Intl.NumberFormat("es-CO", {
            style: "currency",
            currency: "COP",
            maximumFractionDigits: 0,
        }),
        ARS: new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            maximumFractionDigits: 0,
        }),
        MXN: new Intl.NumberFormat("es-MX", {
            style: "currency",
            currency: "MXN",
        }),
        CLP: new Intl.NumberFormat("es-CL", {
            style: "currency",
            currency: "CLP",
            maximumFractionDigits: 0,
        }),
        BRL: new Intl.NumberFormat("pt-BR", {
            style: "currency",
            currency: "BRL",
        }),
    };
    return formatters[currency]?.format(amount) ?? `$${amount.toFixed(2)}`;
}
