import * as Sentry from '@sentry/nextjs';
import { getActiveReplayId } from './sentry';

/**
 * Client-side rage-click detection.
 *
 * Sentry's own dead/rage-click analysis happens server-side on replayed
 * segments. This module gives us an immediate, explicit signal: when a user
 * hammers one element (≥ TRIGGER_COUNT clicks inside WINDOW_MS), we emit a
 * breadcrumb + warning event tagged as a rage click.
 *
 * The event is automatically linked to the active session replay (via the
 * `replay_id` tag set on capture), so it shows up in both the issue list and
 * the replay viewer.
 */

const WINDOW_MS = 2500; // rolling burst window
const TRIGGER_COUNT = 5; // clicks on the same element within the window
const COOLDOWN_MS = 10000; // min gap between two flags for the same element

interface ClickStamp {
  el: string;
  at: number;
}

let burstHistory: ClickStamp[] = [];
const lastFlaggedAt = new Map<string, number>();

/**
 * Build a stable, human-readable key for the clicked element. Prefers an
 * interactive ancestor so stray clicks on a child of a button still count.
 */
function elementKey(target: EventTarget | null): string | null {
  if (!(target instanceof HTMLElement)) {
    return null;
  }

  const interactive = target.closest(
    'button, a[href], [role="button"], input, select, textarea, [onclick], [data-rage-click]'
  );
  const el = (interactive as HTMLElement) ?? target;

  if (el.id) {
    return `#${el.id}`;
  }

  const marker = el.getAttribute('data-rage-click');
  if (marker) {
    return `[data-rage-click="${marker}"]`;
  }

  const classes = el.classList.length
    ? `.${Array.from(el.classList).slice(0, 2).join('.')}`
    : '';
  const text = (el.textContent || '').trim().slice(0, 40);

  return `${el.tagName.toLowerCase()}${classes}:"${text}"`;
}

function handleClick(event: MouseEvent): void {
  try {
    const now = Date.now();
    const key = elementKey(event.target);

    if (!key) {
      return;
    }

    burstHistory.push({ el: key, at: now });
    // Drop anything outside the rolling window.
    burstHistory = burstHistory.filter((h) => now - h.at <= WINDOW_MS);

    const recent = burstHistory.filter((h) => h.el === key).length;

    if (recent < TRIGGER_COUNT) {
      return;
    }

    const lastFlagged = lastFlaggedAt.get(key) ?? 0;
    if (now - lastFlagged < COOLDOWN_MS) {
      return;
    }

    lastFlaggedAt.set(key, now);

    const replayId = getActiveReplayId();

    Sentry.addBreadcrumb({
      category: 'ui.rage_click',
      message: `Potential rage click on ${key}`,
      level: 'warning',
      timestamp: now / 1000,
    });

    Sentry.captureMessage('Rage click detected', {
      level: 'warning',
      tags: {
        rage_click: 'true',
        ...(replayId ? { replay_id: replayId } : {}),
      },
      extra: {
        element: key,
        clicks: recent,
        windowMs: WINDOW_MS,
        url: window.location.href,
      },
    });
  } catch {
    // Detection must never interfere with the app.
  }
}

/**
 * Install the rage-click listener. Safe to call multiple times.
 * Runs before hydration from `src/instrumentation-client.ts`.
 */
export function initRageClickDetection(): void {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    window.addEventListener('click', handleClick, { capture: true, passive: true });
  } catch {
    // noop
  }
}