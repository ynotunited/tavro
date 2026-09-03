/**
 * Input sanitization utilities.
 * Applied before every API call to prevent whitespace, injection, and malformed data.
 */

/** Trim all string values in a flat or shallow-nested object. */
export function trimStrings<T extends Record<string, unknown>>(obj: T): T {
  const result: Record<string, unknown> = { ...obj };
  for (const key of Object.keys(result)) {
    const val = result[key];
    if (typeof val === 'string') {
      result[key] = val.trim();
    }
  }
  return result as T;
}

/** Sanitize a string value: trim, collapse internal whitespace, strip null bytes. */
export function sanitizeString(value: string): string {
  return value
    .replace(/\0/g, '')           // strip null bytes
    .replace(/\s+/g, ' ')         // collapse internal whitespace
    .trim();
}

/** Sanitize email: trim, lowercase, strip null bytes. */
export function sanitizeEmail(email: string): string {
  return sanitizeString(email).toLowerCase();
}

/** Ensure a value is a safe integer within bounds. */
export function safeInt(value: unknown, min: number = 0, max: number = Number.MAX_SAFE_INTEGER): number | null {
  const n = Number(value);
  if (!Number.isFinite(n) || !Number.isInteger(n)) return null;
  return Math.max(min, Math.min(max, n));
}

/** Ensure a value is a safe float within bounds. */
export function safeFloat(value: unknown, min: number = 0, max: number = 999999999): number | null {
  const n = parseFloat(String(value));
  if (!Number.isFinite(n)) return null;
  return Math.max(min, Math.min(max, n));
}
