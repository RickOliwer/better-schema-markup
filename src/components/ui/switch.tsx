import { Switch as BaseSwitch } from "@base-ui/react/switch";

interface SwitchProps {
  readonly id?: string;
  readonly checked: boolean;
  readonly onChange: (checked: boolean) => void;
  readonly disabled?: boolean;
  readonly "aria-describedby"?: string;
}

/** An on/off switch built on Base UI's Switch, styled like shadcn/ui. */
export function Switch({ id, checked, onChange, disabled, "aria-describedby": describedBy }: SwitchProps) {
  return (
    <BaseSwitch.Root
      id={id}
      checked={checked}
      disabled={disabled}
      aria-describedby={describedBy}
      onCheckedChange={(next) => onChange(next)}
      className={
        "bsm:inline-flex bsm:h-5 bsm:w-9 bsm:shrink-0 bsm:cursor-pointer bsm:items-center bsm:rounded-full bsm:border-2 bsm:border-solid bsm:border-transparent bsm:bg-input bsm:p-0 bsm:shadow-xs bsm:transition-colors bsm:outline-none " +
        "bsm:focus-visible:ring-[3px] bsm:focus-visible:ring-ring/50 bsm:data-checked:bg-primary bsm:data-disabled:cursor-not-allowed bsm:data-disabled:opacity-50"
      }
    >
      <BaseSwitch.Thumb className="bsm:pointer-events-none bsm:block bsm:size-4 bsm:rounded-full bsm:bg-background bsm:shadow-sm bsm:transition-transform bsm:data-checked:translate-x-4" />
    </BaseSwitch.Root>
  );
}
