import { __ } from "@wordpress/i18n";
import type { ReactNode } from "react";

import { cn } from "./utils";

interface AlertProps {
  readonly tone?: "info" | "success" | "warning" | "error";
  readonly onDismiss?: () => void;
  readonly children: ReactNode;
}

/** A message box, styled like shadcn/ui's Alert. Errors are announced to screen readers. */
export function Alert({ tone = "info", onDismiss, children }: AlertProps) {
  return (
    <div
      role={tone === "error" ? "alert" : "status"}
      className={cn(
        "bsm:flex bsm:items-start bsm:justify-between bsm:gap-3 bsm:rounded-md bsm:border bsm:border-solid bsm:px-4 bsm:py-3 bsm:text-sm bsm:leading-normal",
        tone === "info" && "bsm:border-border bsm:bg-muted bsm:text-foreground",
        tone === "success" && "bsm:border-success/30 bsm:bg-success/10 bsm:text-foreground",
        tone === "warning" && "bsm:border-warning/40 bsm:bg-warning/10 bsm:text-foreground",
        tone === "error" && "bsm:border-destructive/30 bsm:bg-destructive/10 bsm:text-destructive",
      )}
    >
      <div>{children}</div>
      {onDismiss ? (
        <button
          type="button"
          onClick={onDismiss}
          aria-label={__("Dismiss", "better-schema-markup")}
          className="bsm:-my-1 bsm:-mr-2 bsm:inline-flex bsm:size-7 bsm:cursor-pointer bsm:items-center bsm:justify-center bsm:rounded-md bsm:border-0 bsm:bg-transparent bsm:p-0 bsm:text-current bsm:hover:bg-black/5"
        >
          <svg
            width="14"
            height="14"
            viewBox="0 0 16 16"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
            aria-hidden="true"
          >
            <path d="m4 4 8 8M12 4l-8 8" strokeLinecap="round" />
          </svg>
        </button>
      ) : null}
    </div>
  );
}
