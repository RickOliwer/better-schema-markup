import type { ButtonHTMLAttributes } from "react";

import { cn } from "./utils";

// Each variant sets its own border, because classes are not merged (see utils.ts):
// a shared `border-0` would override the outline variant's `border`.
const VARIANTS = {
  default: "bsm:border-0 bsm:bg-primary bsm:text-primary-foreground bsm:hover:bg-primary/90",
  outline: "bsm:border bsm:border-solid bsm:border-input bsm:bg-background bsm:text-foreground bsm:shadow-xs bsm:hover:bg-accent",
  ghost: "bsm:border-0 bsm:bg-transparent bsm:text-foreground bsm:hover:bg-accent",
  destructive: "bsm:border-0 bsm:bg-transparent bsm:text-destructive bsm:hover:bg-destructive/10",
} as const;

const SIZES = {
  default: "bsm:h-9 bsm:px-4",
  sm: "bsm:h-8 bsm:px-3",
  icon: "bsm:size-6 bsm:p-0",
} as const;

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  readonly variant?: keyof typeof VARIANTS;
  readonly size?: keyof typeof SIZES;
  readonly isBusy?: boolean;
}

export function Button({ variant = "default", size = "default", isBusy = false, className, type = "button", disabled, children, ...props }: ButtonProps) {
  return (
    <button
      type={type}
      disabled={disabled || isBusy}
      aria-busy={isBusy || undefined}
      className={cn(
        "bsm:inline-flex bsm:shrink-0 bsm:cursor-pointer bsm:items-center bsm:justify-center bsm:gap-2 bsm:rounded-md bsm:text-sm bsm:font-medium bsm:whitespace-nowrap bsm:transition-colors",
        "bsm:outline-none bsm:focus-visible:ring-[3px] bsm:focus-visible:ring-ring/50 bsm:disabled:pointer-events-none bsm:disabled:opacity-50",
        VARIANTS[variant],
        SIZES[size],
        className,
      )}
      {...props}
    >
      {isBusy ? <Spinner /> : null}
      {children}
    </button>
  );
}

export function Spinner({ className }: { readonly className?: string }) {
  return (
    <svg className={cn("bsm:size-4 bsm:animate-spin", className)} viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="9" stroke="currentColor" strokeOpacity="0.25" strokeWidth="3" />
      <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
    </svg>
  );
}
