import { Radio } from "@base-ui/react/radio";
import { RadioGroup as BaseRadioGroup } from "@base-ui/react/radio-group";
import { useId } from "react";

export interface RadioChoice<Value extends string> {
  readonly value: Value;
  readonly label: string;
  readonly description?: string;
}

interface RadioGroupProps<Value extends string> {
  /** Id of the element that labels the group. */
  readonly labelledBy: string;
  readonly value: Value;
  readonly choices: readonly RadioChoice<Value>[];
  readonly onChange: (value: Value) => void;
}

/**
 * Radio buttons as bordered choice cards, like shadcn/ui's Choice Card, built on
 * Base UI's RadioGroup. Clicking anywhere on a card selects it.
 */
export function RadioGroup<Value extends string>({
  labelledBy,
  value,
  choices,
  onChange,
}: RadioGroupProps<Value>) {
  const id = useId();

  return (
    <BaseRadioGroup
      aria-labelledby={labelledBy}
      value={value}
      onValueChange={(next) => {
        const choice = choices.find((candidate) => candidate.value === next);
        if (choice) onChange(choice.value);
      }}
      className="bsm:grid bsm:gap-2"
    >
      {choices.map((choice) => (
        <label
          key={choice.value}
          className="bsm:flex bsm:cursor-pointer bsm:items-start bsm:gap-3 bsm:rounded-lg bsm:border bsm:border-solid bsm:border-border bsm:p-3 bsm:transition-colors bsm:hover:bg-accent bsm:has-data-checked:border-primary bsm:has-data-checked:bg-accent"
        >
          <Radio.Root
            value={choice.value}
            aria-describedby={choice.description ? `${id}-${choice.value}` : undefined}
            className="bsm:mt-0.5 bsm:flex bsm:size-4 bsm:shrink-0 bsm:cursor-pointer bsm:items-center bsm:justify-center bsm:rounded-full bsm:border bsm:border-solid bsm:border-input bsm:bg-background bsm:p-0 bsm:shadow-xs bsm:outline-none bsm:focus-visible:ring-[3px] bsm:focus-visible:ring-ring/50 bsm:data-checked:border-primary"
          >
            <Radio.Indicator className="bsm:size-2 bsm:rounded-full bsm:bg-primary bsm:data-unchecked:hidden" />
          </Radio.Root>
          <span className="bsm:grid bsm:gap-0.5">
            <span className="bsm:text-sm bsm:leading-snug bsm:font-medium bsm:text-foreground">
              {choice.label}
            </span>
            {choice.description ? (
              <span
                id={`${id}-${choice.value}`}
                className="bsm:text-sm bsm:leading-normal bsm:text-muted-foreground"
              >
                {choice.description}
              </span>
            ) : null}
          </span>
        </label>
      ))}
    </BaseRadioGroup>
  );
}
