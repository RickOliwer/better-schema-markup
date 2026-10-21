import type { InputHTMLAttributes, TextareaHTMLAttributes } from "react";

import { cn } from "./utils";

// wp-admin styles inputs with `min-height`, `line-height` and a box shadow, so those are reset here too.
const CONTROL =
  "bsm:w-full bsm:min-w-0 bsm:m-0 bsm:rounded-md bsm:border bsm:border-solid bsm:border-input bsm:bg-background bsm:px-3 bsm:text-sm bsm:text-foreground bsm:shadow-xs bsm:transition-[color,box-shadow] bsm:outline-none " +
  "bsm:placeholder:text-muted-foreground bsm:focus-visible:border-ring bsm:focus-visible:ring-[3px] bsm:focus-visible:ring-ring/50 " +
  "bsm:aria-invalid:border-destructive bsm:aria-invalid:ring-destructive/20 bsm:disabled:cursor-not-allowed bsm:disabled:opacity-50";

export function Input({ className, type = "text", ...props }: InputHTMLAttributes<HTMLInputElement>) {
  return <input type={type} className={cn(CONTROL, "bsm:h-9 bsm:min-h-0 bsm:py-1 bsm:leading-normal", className)} {...props} />;
}

export function Textarea({ className, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return <textarea className={cn(CONTROL, "bsm:min-h-16 bsm:py-2 bsm:leading-normal", className)} {...props} />;
}
