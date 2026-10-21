import { Popover } from "@base-ui/react/popover";
import { __, sprintf } from "@wordpress/i18n";
import type { ReactNode } from "react";

interface HelpPopoverProps {
  /** The field or section the help is about, used to name the button for screen readers. */
  readonly topic: string;
  readonly children: ReactNode;
}

/**
 * A "?" button that opens a short explanation. Base UI closes it on Escape or a
 * click outside, and returns focus to the button.
 *
 * Keep it outside `<label>` elements, so clicking it never toggles or focuses a field.
 */
export function HelpPopover({ topic, children }: HelpPopoverProps) {
  return (
    <Popover.Root>
      <Popover.Trigger
        aria-label={sprintf(
          /* translators: %s: name of the field or section the help is about. */
          __("Help: %s", "better-schema-markup"),
          topic,
        )}
        className="bsm:inline-flex bsm:size-6 bsm:shrink-0 bsm:cursor-pointer bsm:items-center bsm:justify-center bsm:rounded-full bsm:border-0 bsm:bg-transparent bsm:p-0 bsm:text-muted-foreground bsm:outline-none bsm:hover:bg-accent bsm:hover:text-foreground bsm:focus-visible:ring-[3px] bsm:focus-visible:ring-ring/50 bsm:data-popup-open:bg-accent bsm:data-popup-open:text-foreground"
      >
        <svg
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          aria-hidden="true"
        >
          <circle cx="12" cy="12" r="10" />
          <path
            d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Positioner
          className="bsm:z-[100000]"
          side="bottom"
          align="start"
          sideOffset={6}
          collisionPadding={16}
        >
          <Popover.Popup
            className={
              "bsm:w-80 bsm:max-w-[calc(100vw-2rem)] bsm:origin-[var(--transform-origin)] bsm:rounded-md bsm:border bsm:border-solid bsm:border-border bsm:bg-background bsm:p-4 bsm:text-sm bsm:leading-relaxed bsm:text-foreground bsm:shadow-md bsm:outline-none " +
              "bsm:transition-[scale,opacity] bsm:duration-100 bsm:data-starting-style:scale-95 bsm:data-starting-style:opacity-0 bsm:data-ending-style:scale-95 bsm:data-ending-style:opacity-0"
            }
          >
            <Popover.Description className="bsm:m-0">{children}</Popover.Description>
          </Popover.Popup>
        </Popover.Positioner>
      </Popover.Portal>
    </Popover.Root>
  );
}
