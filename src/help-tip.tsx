import { Button, Popover } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { useEffect, useRef, useState } from "react";
import type { ReactNode } from "react";

interface HelpTipProps {
  /** The field the help is about, used to name the button for screen readers. */
  readonly topic: string;
  readonly children: ReactNode;
}

/** A "?" button that opens a short explanation next to a field. */
export function HelpTip({ topic, children }: HelpTipProps) {
  const [isOpen, setIsOpen] = useState(false);
  const buttonArea = useRef<HTMLSpanElement>(null);
  const content = useRef<HTMLDivElement>(null);

  // Clicking anywhere else closes the help. That includes another help button, so only one is open.
  useEffect(() => {
    if (!isOpen) return;

    function closeOnOutsidePress(event: PointerEvent) {
      const target = event.target;
      if (!(target instanceof Node)) return;
      if (buttonArea.current?.contains(target) || content.current?.contains(target)) return;
      setIsOpen(false);
    }

    document.addEventListener("pointerdown", closeOnOutsidePress, true);
    return () => document.removeEventListener("pointerdown", closeOnOutsidePress, true);
  }, [isOpen]);

  return (
    <span
      ref={buttonArea}
      // The spacing is padding, not margin, so it belongs to this area. Clicks here must not
      // reach the field's label, which would otherwise toggle or focus the field.
      onClick={(event) => event.preventDefault()}
      style={{ display: "inline-block", paddingLeft: "4px", verticalAlign: "middle" }}
    >
      <Button
        size="small"
        label={sprintf(
          /* translators: %s: name of the field the help is about. */
          __("Help: %s", "better-schema-markup"),
          topic,
        )}
        aria-expanded={isOpen}
        onClick={(event: { preventDefault(): void }) => {
          // The button sits inside a field label, where a click would otherwise focus the field.
          event.preventDefault();
          setIsOpen((open) => !open);
        }}
        icon={
          <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path
              fill="currentColor"
              d="M12 3.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 0 0 0-17ZM2 12C2 6.48 6.48 2 12 2s10 4.48 10 10-4.48 10-10 10S2 17.52 2 12Zm9.25 4.25h1.5V17.5h-1.5v-1.25Zm1.4-8.4c-1.35-.4-2.9.3-3.15 1.9l1.48.24c.1-.62.67-.95 1.24-.78.5.15.78.6.7 1.06-.1.52-.5.8-1 1.1-.55.34-1.12.77-1.12 1.63v.5h1.5v-.4c0-.27.2-.4.6-.64.67-.4 1.5-.92 1.7-2.1.2-1.2-.6-2.2-1.95-2.51Z"
            />
          </svg>
        }
      />
      {isOpen ? (
        <Popover placement="bottom-start" onClose={() => setIsOpen(false)} focusOnMount={false} offset={8}>
          <div
            ref={content}
            style={{ boxSizing: "border-box", width: "300px", maxWidth: "calc(100vw - 48px)", padding: "12px 16px", fontSize: "13px", lineHeight: 1.5, whiteSpace: "normal", textTransform: "none", fontWeight: 400 }}
          >
            {children}
          </div>
        </Popover>
      ) : null}
    </span>
  );
}

interface LabelWithHelpProps {
  readonly label: string;
  readonly children: ReactNode;
}

/** A field label followed by a help button, for the `label` prop of WordPress form controls. */
export function LabelWithHelp({ label, children }: LabelWithHelpProps) {
  return (
    <>
      {label}
      <HelpTip topic={label}>{children}</HelpTip>
    </>
  );
}
