package engine

import (
	"math/rand"
	"strings"
)

// Traits modifiers
func ApplyGrintoso(scoreDifference int, baseSkill float64) float64 {
	// If losing by 1 or more, +10% boost
	if scoreDifference < 0 {
		return baseSkill * 1.10
	}
	return baseSkill
}

func ApplyCarismatico(isCaptain bool, teamSkill float64) float64 {
	if isCaptain {
		return teamSkill * 1.05
	}
	return teamSkill
}

func ApplyIrrequieto(baseSkill float64) (float64, bool) {
	// 5% chance of getting a card, but +10% skill
	hasCard := rand.Intn(100) < 5
	return baseSkill * 1.10, hasCard
}

func normalizeTrait(v string) string {
	return strings.ToLower(strings.TrimSpace(v))
}

// MatchTraitScalar returns a single-match scalar for a player trait.
// Go engine is team-level, so we use averaged player trait scalars.
func MatchTraitScalar(character string, isLosing bool, isHome bool) float64 {
	switch normalizeTrait(character) {
	case "grintoso":
		if isLosing {
			return 1.10
		}
		return 1.00
	case "ambizioso":
		return 1.05
	case "razionale":
		if isHome {
			return 1.00
		}
		return 1.05
	case "irrequieto":
		if isHome {
			return 1.10
		}
		return 0.95
	case "popolare":
		return 1.02
	case "inflessibile":
		return 1.03
	case "duttile":
		return 1.01
	case "costante":
		return 1.01
	default:
		return 1.00
	}
}
