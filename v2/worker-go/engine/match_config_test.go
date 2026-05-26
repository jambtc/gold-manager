package engine

import "testing"

func TestHalfTimePauseTicksDefaultAndClamp(t *testing.T) {
	t.Setenv("GM_HALF_TIME_TICKS", "")
	if got := halfTimePauseTicks(); got != 15 {
		t.Fatalf("default halfTimePauseTicks = %d; want 15", got)
	}

	t.Setenv("GM_HALF_TIME_TICKS", "20")
	if got := halfTimePauseTicks(); got != 20 {
		t.Fatalf("configured halfTimePauseTicks = %d; want 20", got)
	}

	t.Setenv("GM_HALF_TIME_TICKS", "0")
	if got := halfTimePauseTicks(); got != 1 {
		t.Fatalf("min clamp halfTimePauseTicks = %d; want 1", got)
	}

	t.Setenv("GM_HALF_TIME_TICKS", "500")
	if got := halfTimePauseTicks(); got != 120 {
		t.Fatalf("max clamp halfTimePauseTicks = %d; want 120", got)
	}

	t.Setenv("GM_HALF_TIME_TICKS", "abc")
	if got := halfTimePauseTicks(); got != 15 {
		t.Fatalf("invalid halfTimePauseTicks = %d; want 15", got)
	}
}
