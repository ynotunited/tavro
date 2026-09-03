import * as Sentry from '@sentry/nextjs';

/**
 * Return the active Session Replay id, if any.
 *
 * Replay ids exist only once a sampled session has actually started, so callers
 * must treat `undefined` as "no replay in progress".
 */
export function getActiveReplayId(): string | undefined {
  try {
    const replay = Sentry.getReplay?.();
    return replay?.getReplayId?.() || undefined;
  } catch {
    return undefined;
  }
}

/**
 * Attach the active replay id to an event, both as a tag and in the standard
 * `replay` context. This is what connects session replays to error tracking.
 *
 * Sentry also links replays automatically when `replaysOnErrorSampleRate` is
 * enabled; this explicit stamp covers rage-click messages and any other custom
 * event we capture.
 */
export function stampReplayId(event: Sentry.ErrorEvent): Sentry.ErrorEvent | null {
  try {
    const replayId = getActiveReplayId();

    if (replayId) {
      event.tags = {
        ...(event.tags ?? {}),
        replay_id: replayId,
      };
      event.contexts = {
        ...(event.contexts ?? {}),
        replay: { replay_id: replayId },
      };
    }

    return event;
  } catch {
    return event;
  }
}

/**
 * Read the persisted auth session (zustand persist store, key `tavro-auth`) and
 * set Sentry user/org context so errors and replays are attributed to the right
 * tenant. Called once at app start (instrumentation-client).
 */
export function syncSentryAuthContext(): void {
  try {
    if (typeof window === 'undefined') {
      return;
    }

    const raw = window.localStorage.getItem('tavro-auth');
    if (!raw) {
      Sentry.setUser(null);
      return;
    }

    const parsed = JSON.parse(raw) as {
      state?: {
        user?: {
          id: number | string;
          first_name?: string;
          last_name?: string;
          email?: string;
          organization_id?: number | null;
          branch_id?: number | null;
        } | null;
      };
    };

    const user = parsed?.state?.user;

    if (user && typeof user.id !== 'undefined' && user.id !== null) {
      Sentry.setUser({
        id: String(user.id),
        email: user.email,
        username: [user.first_name, user.last_name].filter(Boolean).join(' '),
      });
      Sentry.setTag('organization_id', String(user.organization_id ?? ''));
      Sentry.setTag('branch_id', String(user.branch_id ?? ''));
    } else {
      Sentry.setUser(null);
    }
  } catch {
    // Never let context syncing break app startup.
    Sentry.setUser(null);
  }
}