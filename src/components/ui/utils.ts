import { clsx } from "clsx";
import type { ClassValue } from "clsx";

/**
 * Joins class names and drops falsy values.
 *
 * Unlike shadcn/ui's `cn`, this does not resolve conflicting Tailwind classes
 * (tailwind-merge would add about 25 KB). Pass only additional classes through
 * `className`, never ones that replace a component's own.
 */
export function cn(...classes: ClassValue[]): string {
  return clsx(classes);
}
