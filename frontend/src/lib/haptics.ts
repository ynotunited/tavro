/**
 * Utility for triggering haptic feedback on mobile devices using the Vibration API.
 * Safely degrades on unsupported browsers (e.g. desktop).
 */

export const haptics = {
  /** Light tap, good for standard button presses */
  light: () => {
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
      navigator.vibrate(10);
    }
  },
  
  /** Medium tap, good for important actions like 'Send Order' */
  medium: () => {
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
      navigator.vibrate(30);
    }
  },
  
  /** Heavy tap, good for destructive actions or primary completions */
  heavy: () => {
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
      navigator.vibrate(50);
    }
  },
  
  /** Success pattern */
  success: () => {
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
      navigator.vibrate([10, 50, 30]);
    }
  },
  
  /** Error/Warning pattern (staccato buzz) */
  error: () => {
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
      navigator.vibrate([50, 50, 50, 50, 50]);
    }
  }
};
