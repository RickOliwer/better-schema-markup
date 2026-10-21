import { describe, expect, it } from "vite-plus/test";

import { cn } from "./utils";

describe("cn", () => {
  it("joins classes and drops falsy values", () => {
    expect(
      cn("bsm:flex", false, undefined, "bsm:gap-2", { "bsm:p-4": true, "bsm:p-2": false }),
    ).toBe("bsm:flex bsm:gap-2 bsm:p-4");
  });
});
