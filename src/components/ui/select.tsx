import { Select as BaseSelect } from "@base-ui/react/select";

import { cn } from "./utils";

export interface SelectOption<Value extends string> {
  readonly label: string;
  readonly value: Value;
}

interface SelectProps<Value extends string> {
  readonly id?: string;
  readonly value: Value;
  readonly options: readonly SelectOption<Value>[];
  readonly onChange: (value: Value) => void;
  readonly disabled?: boolean;
  readonly "aria-describedby"?: string;
}

/** A single-choice dropdown built on Base UI's Select, styled like shadcn/ui. */
export function Select<Value extends string>({ id, value, options, onChange, disabled, "aria-describedby": describedBy }: SelectProps<Value>) {
  return (
    <BaseSelect.Root
      items={options.map((option) => ({ label: option.label, value: option.value }))}
      value={value}
      disabled={disabled}
      onValueChange={(next) => {
        const option = options.find((candidate) => candidate.value === next);
        if (option) onChange(option.value);
      }}
    >
      <BaseSelect.Trigger
        id={id}
        aria-describedby={describedBy}
        className={cn(
          "bsm:flex bsm:h-9 bsm:w-full bsm:cursor-pointer bsm:items-center bsm:justify-between bsm:gap-2 bsm:rounded-md bsm:border bsm:border-solid bsm:border-input bsm:bg-background bsm:px-3 bsm:text-sm bsm:text-foreground bsm:shadow-xs bsm:outline-none bsm:select-none",
          "bsm:focus-visible:border-ring bsm:focus-visible:ring-[3px] bsm:focus-visible:ring-ring/50 bsm:data-disabled:cursor-not-allowed bsm:data-disabled:opacity-50",
        )}
      >
        <BaseSelect.Value className="bsm:truncate" />
        <BaseSelect.Icon className="bsm:flex bsm:text-muted-foreground">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
            <path d="m4 6 4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </BaseSelect.Icon>
      </BaseSelect.Trigger>
      <BaseSelect.Portal>
        <BaseSelect.Positioner className="bsm:z-[100000] bsm:outline-none" sideOffset={4} alignItemWithTrigger={false}>
          <BaseSelect.Popup
            className={cn(
              "bsm:min-w-[var(--anchor-width)] bsm:origin-[var(--transform-origin)] bsm:rounded-md bsm:border bsm:border-solid bsm:border-border bsm:bg-background bsm:p-1 bsm:text-foreground bsm:shadow-md bsm:outline-none",
              "bsm:transition-[scale,opacity] bsm:duration-100 bsm:data-starting-style:scale-95 bsm:data-starting-style:opacity-0 bsm:data-ending-style:scale-95 bsm:data-ending-style:opacity-0",
            )}
          >
            <BaseSelect.List className="bsm:max-h-[var(--available-height)] bsm:overflow-y-auto">
              {options.map((option) => (
                <BaseSelect.Item
                  key={option.value}
                  value={option.value}
                  className="bsm:grid bsm:cursor-pointer bsm:grid-cols-[1rem_1fr] bsm:items-center bsm:gap-2 bsm:rounded-sm bsm:py-1.5 bsm:pr-3 bsm:pl-2 bsm:text-sm bsm:outline-none bsm:select-none bsm:data-highlighted:bg-accent bsm:data-highlighted:text-accent-foreground"
                >
                  <BaseSelect.ItemIndicator className="bsm:col-start-1 bsm:flex">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
                      <path d="m3.5 8.5 3 3 6-7" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                  </BaseSelect.ItemIndicator>
                  <BaseSelect.ItemText className="bsm:col-start-2">{option.label}</BaseSelect.ItemText>
                </BaseSelect.Item>
              ))}
            </BaseSelect.List>
          </BaseSelect.Popup>
        </BaseSelect.Positioner>
      </BaseSelect.Portal>
    </BaseSelect.Root>
  );
}
