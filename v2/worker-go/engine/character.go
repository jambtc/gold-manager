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

// effortGoalMod returns the performance multiplier for a given effort level (SIP-0083).
func effortGoalMod(level int) float64 {
	switch level {
	case 0:
		return 0.88
	case 25:
		return 0.95
	case 75:
		return 1.04
	case 100:
		return 1.08
	default:
		return 1.00 // 50
	}
}

// effortInjuryMod returns the injury risk multiplier for a given effort level.
func effortInjuryMod(level int) float64 {
	switch level {
	case 0:
		return 0.70
	case 25:
		return 0.90
	case 75:
		return 1.20
	case 100:
		return 1.45
	default:
		return 1.00
	}
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
	case "introverso":
		// SIP-0024: −5% pass/performance when away (SIP spec alignment).
		if isHome {
			return 1.00
		}
		return 0.95
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
	case "egoista":
		// SIP-0024: selfish player pushes harder individually (+8%) but
		// only when home (crowd fuels ego); small penalty away.
		if isHome {
			return 1.08
		}
		return 0.97
	case "fantasioso":
		// SIP-0024: creative but inconsistent; net average +3%.
		return 1.03
	case "corretto":
		// SIP-0024: fair-play → piccolo bonus stabilità
		return 1.03
	case "carismatico":
		// SIP-0024: leader → trascina i compagni
		return 1.03
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
