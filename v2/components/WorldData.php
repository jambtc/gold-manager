<?php

declare(strict_types=1);

namespace app\components;

class WorldData
{
    public const FIRST_NAMES = [
        'Alessandro', 'Andrea', 'Antonio', 'Carlo', 'Christian',
        'Davide', 'Emanuele', 'Fabio', 'Federico', 'Francesco',
        'Gabriele', 'Giacomo', 'Giorgio', 'Giovanni', 'Giuseppe',
        'Luca', 'Marco', 'Massimo', 'Matteo', 'Michele',
        'Nicola', 'Paolo', 'Pietro', 'Riccardo', 'Roberto',
        'Salvatore', 'Simone', 'Stefano', 'Tommaso', 'Vincenzo',
        'Alberto', 'Alfredo', 'Angelo', 'Bruno', 'Claudio',
        'Daniele', 'Diego', 'Edoardo', 'Enrico', 'Enzo',
        'Filippo', 'Gianluca', 'Gianni', 'Giulio', 'Igor',
        'Leonardo', 'Lorenzo', 'Luigi', 'Mauro', 'Mirko',
        'Moreno', 'Pasquale', 'Raffaele', 'Renato', 'Rocco',
        'Sergio', 'Silvio', 'Ugo', 'Valentino', 'Vittorio',
        'Adriano', 'Alan', 'Aldo', 'Alessio', 'Alex',
        'Alfio', 'Amedeo', 'Armando', 'Arturo', 'Attilio',
        'Cesare', 'Corrado', 'Cosimo', 'Dario', 'Dino',
        'Domenico', 'Donato', 'Elio', 'Emilio', 'Ernesto',
        'Ettore', 'Eugenio', 'Ezio', 'Fausto', 'Fernando',
        'Flavio', 'Franco', 'Fulvio', 'Gennaro', 'Giordano',
        'Giuliano', 'Guido', 'Ignazio', 'Italo', 'Ivano',
        'Luciano', 'Manlio', 'Mario', 'Nino', 'Orazio',
        'Piero', 'Rino', 'Sandro', 'Tiziano', 'Walter',
    ];

    public const LAST_NAMES = [
        'Rossi', 'Russo', 'Ferrari', 'Esposito', 'Bianchi',
        'Romano', 'Colombo', 'Ricci', 'Marino', 'Greco',
        'Bruno', 'Gallo', 'Conti', 'De Luca', 'Mancini',
        'Costa', 'Giordano', 'Rizzo', 'Lombardi', 'Moretti',
        'Barbieri', 'Fontana', 'Santoro', 'Mariani', 'Rinaldi',
        'Caruso', 'Ferrara', 'Gatti', 'Monti', 'Vitale',
        'Serra', 'Coppola', 'De Santis', 'Marchetti', 'Parisi',
        'Villa', 'Conte', 'Ferraro', 'Longo', 'Martini',
        'Palumbo', 'Sanna', 'Farina', 'Gentile', 'Monaco',
        'Sorrentino', 'Cattaneo', 'Pellegrini', 'Sala', 'Valentini',
        'Bernardi', 'Benedetti', 'Barone', 'Graziani', 'Fiore',
        'Battaglia', 'Leone', 'Amato', 'Testa', 'Orlandi',
        'Bianco', 'De Rosa', 'Ferri', 'Palma', 'Piras',
        'Mazza', 'Grasso', 'Ferretti', 'Riva', 'Brambilla',
        'Caputo', 'Donati', 'Bassi', 'Ruggieri', 'Orlando',
        'Martino', 'Bellini', 'Neri', 'Silvestri', 'Crespi',
        'Carbone', 'Zanetti', 'Gagliardi', 'Lupo', 'Moro',
        'Negri', 'Ravasi', 'Taddei', 'Viviani', 'Cattani',
        'Fontanesi', 'Improta', 'Osti', 'Pagnini', 'Quartieri',
        'Sassoli', 'Ungaro', 'Borrelli', 'Catalano', 'D\'Agostino',
        'Damiani', 'Del Vecchio', 'Fabbri', 'Falco', 'Grassi',
        'Gualtieri', 'Iannone', 'La Rosa', 'Mele', 'Napolitano',
        'Pinto', 'Pugliese', 'Quaglia', 'Ragusa', 'Schiavone',
        'Spada', 'Todaro', 'Troia', 'Vella', 'Zaccaria',
    ];

    public const TEAM_NAMES = [
        'Atletico Riviera', 'FC Montebello', 'Unione Castelforte',
        'Sporting Altavalle', 'Calcio Miramare', 'Torrente FC',
        'Borghetto United', 'Rocca Calcio', 'Stella Adriatica',
        'Aquile Verdi', 'FC Bellariva', 'Città di Montagna',
        'Rapid Colli', 'Pro Vallesana', 'Folgore Litorale',
        'Virtus Pianura', 'Aurora Calcio', 'Fortis Borgo',
        'Polisportiva Trebbia', 'Olimpia Lacuale',
        'Accademia Castellana', 'Dinamica Ponente',
        'FC Portochiaro', 'Leoni del Sud', 'Falchi Neri',
        'Nuova Primavera FC', 'Atletica Collinare',
        'Granata Calcio', 'Biancazzurri Riuniti', 'Arancioni FC',
        'Vigor Meridionale', 'Sporting Fontanelle',
        'Unione Terrazze', 'Calcio Ponterosso',
        'Meteora Calcio', 'FC Vallecalda', 'Ala d\'Argento',
        'Tempesta FC', 'Libertas Calcio', 'Pro Montagna',
        'Azzurri Levante', 'Torri Unite', 'Real Pedemonte',
        'FC Laguna', 'Aquila Bianca', 'Rovers del Nord',
        'Corsa Calcio', 'Martelli FC', 'Balilla Calcio',
        'FC Cenacolo', 'Pionieri United', 'Capo Nera FC',
        'Radici Calcio', 'Ponte Vecchio FC', 'Faro Azzurro',
        'FC Terraverde', 'Sporting Borgate', 'Unione Rivoli',
        'Scintilla Calcio', 'Fiamma Rossa FC',
    ];

    public const CHARACTERS = [
        'grintoso', 'ambizioso', 'razionale', 'diligente', 'corretto',
        'duttile', 'inflessibile', 'introverso', 'carismatico', 'popolare',
        'costante', 'irrequieto',
    ];

    // ── SIP-0034: DB-backed name generation with static array fallback ──

    public static function randomFirstName(): string
    {
        try {
            $name = \Yii::$app->db->createCommand(
                'SELECT name FROM {{%name_first}} ORDER BY RAND() LIMIT 1'
            )->queryScalar();
            if ($name) return (string)$name;
        } catch (\Throwable $e) {
            // fallback
        }
        return self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
    }

    public static function randomLastName(): string
    {
        try {
            $name = \Yii::$app->db->createCommand(
                'SELECT name FROM {{%name_last}} ORDER BY RAND() LIMIT 1'
            )->queryScalar();
            if ($name) return (string)$name;
        } catch (\Throwable $e) {
            // fallback
        }
        return self::LAST_NAMES[array_rand(self::LAST_NAMES)];
    }

    /** Returns a stat profile for a position: [primary, secondary, others] = stat keys. */
    public static function statProfile(string $position): array
    {
        return match ($position) {
            'GK' => [
                'primary'   => ['skill_po'],
                'secondary' => ['skill_rg'],
                'low'       => ['skill_df', 'skill_cn', 'skill_pa', 'skill_cr', 'skill_tc', 'skill_tr'],
            ],
            'DF' => [
                'primary'   => ['skill_df', 'skill_tc'],
                'secondary' => ['skill_cn', 'skill_rg'],
                'low'       => ['skill_po', 'skill_pa', 'skill_cr', 'skill_tr'],
            ],
            'MF' => [
                'primary'   => ['skill_cn', 'skill_pa'],
                'secondary' => ['skill_df', 'skill_tc', 'skill_cr'],
                'low'       => ['skill_po', 'skill_rg', 'skill_tr'],
            ],
            'FW' => [
                'primary'   => ['skill_tr', 'skill_cr'],
                'secondary' => ['skill_pa', 'skill_rg'],
                'low'       => ['skill_po', 'skill_df', 'skill_cn', 'skill_tc'],
            ],
            default => throw new \InvalidArgumentException("Unknown position: $position"),
        };
    }

    /** Age range [min, max] for a position. */
    public static function ageRange(string $position): array
    {
        return match ($position) {
            'GK' => [22, 35],
            'DF' => [20, 32],
            'MF' => [19, 31],
            'FW' => [19, 30],
            default => [18, 35],
        };
    }

    /** Standard 20-player roster composition. */
    public static function rosterComposition(): array
    {
        return [
            'GK' => 2,
            'DF' => 6,
            'MF' => 6,
            'FW' => 6,
        ];
    }
}
