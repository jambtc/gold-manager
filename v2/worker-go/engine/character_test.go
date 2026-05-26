package engine

import "testing"

func TestMatchTraitScalar(t *testing.T) {
	tests := []struct {
		name      string
		character string
		isLosing  bool
		isHome    bool
		want      float64
	}{
		{name: "grintoso-losing", character: "grintoso", isLosing: true, isHome: true, want: 1.10},
		{name: "grintoso-not-losing", character: "grintoso", isLosing: false, isHome: true, want: 1.00},
		{name: "ambizioso", character: "ambizioso", isLosing: false, isHome: true, want: 1.05},
		{name: "introverso-home", character: "introverso", isLosing: false, isHome: true, want: 1.00},
		{name: "introverso-away", character: "introverso", isLosing: false, isHome: false, want: 0.95},
		{name: "irrequieto-away", character: "irrequieto", isLosing: false, isHome: false, want: 0.95},
		{name: "unknown", character: "sconosciuto", isLosing: false, isHome: true, want: 1.00},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := MatchTraitScalar(tt.character, tt.isLosing, tt.isHome)
			if got != tt.want {
				t.Fatalf("MatchTraitScalar(%q, losing=%v, home=%v) = %.2f; want %.2f",
					tt.character, tt.isLosing, tt.isHome, got, tt.want)
			}
		})
	}
}
