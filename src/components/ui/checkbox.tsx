import { Checkbox as BaseCheckbox } from "@base-ui/react/checkbox";

interface CheckboxProps {
  readonly id?: string;
  readonly checked: boolean;
  readonly onChange: (checked: boolean) => void;
}

/** A checkbox built on Base UI's Checkbox, styled like shadcn/ui. Pair it with a `<label htmlFor>`. */
export function Checkbox({ id, checked, onChange }: CheckboxProps) {
  return (
    <BaseCheckbox.Root
      id={id}
      checked={checked}
      onCheckedChange={(next) => onChange(next)}
      className={
        "bsm:flex bsm:size-4 bsm:shrink-0 bsm:cursor-pointer bsm:items-center bsm:justify-center bsm:rounded-[4px] bsm:border bsm:border-solid bsm:border-input bsm:bg-background bsm:p-0 bsm:shadow-xs bsm:outline-none " +
        "bsm:focus-visible:ring-[3px] bsm:focus-visible:ring-ring/50 bsm:data-checked:border-primary bsm:data-checked:bg-primary bsm:data-checked:text-primary-foreground"
      }
    >
      <BaseCheckbox.Indicator className="bsm:flex">
        <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
          <path d="m3.5 8.5 3 3 6-7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </BaseCheckbox.Indicator>
    </BaseCheckbox.Root>
  );
}
