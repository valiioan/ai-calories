let counter = 0

export function uid(prefix: string): string {
  counter += 1
  return `${prefix}-${counter}-${Date.now().toString(36)}`
}

export function delay(ms: number): Promise<void> {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms)
  })
}
