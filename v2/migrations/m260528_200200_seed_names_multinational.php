<?php

use yii\db\Migration;

/**
 * Seeds name_first and name_last with multi-national names (SIP-0070).
 * Only inserts rows that don't already exist for that nationality.
 */
class m260528_200200_seed_names_multinational extends Migration
{
    private array $firstNames = [
        'ESP' => [
            'Alejandro','Antonio','Carlos','David','Diego','Eduardo','Fernando','Francisco',
            'Gonzalo','Guillermo','Hugo','Ignacio','Javier','Jorge','José','Juan','Luis',
            'Manuel','Marcos','Martín','Miguel','Pablo','Pedro','Rafael','Raúl','Ricardo',
            'Roberto','Rubén','Sergio','Valentín',
        ],
        'BRA' => [
            'Anderson','Bruno','Carlos','Cristiano','Daniel','Eduardo','Felipe','Fernando',
            'Gabriel','Gustavo','João','Jonathan','José','Júnior','Leonardo','Lucas',
            'Luiz','Marcelo','Mateus','Paulo','Rafael','Renan','Ricardo','Rodrigo',
            'Thiago','Victor','Vinícius','Wagner','Wellington','William',
        ],
        'ARG' => [
            'Agustín','Alejandro','Alfredo','Carlos','Daniel','Diego','Emilio','Facundo',
            'Federico','Gabriel','Hernán','Ignacio','Javier','Juan','Leandro','Lionel',
            'Luis','Marcos','Martín','Matías','Mauro','Miguel','Nicolás','Pablo',
            'Ricardo','Roberto','Rodrigo','Sebastián','Sergio','Tomás',
        ],
        'FRA' => [
            'Alexandre','Alexis','Antoine','Benjamin','Clément','Corentin','Dimitri',
            'Florian','François','Hugo','Jonathan','Julien','Kylian','Laurent','Lucas',
            'Mathieu','Maxime','Moussa','Nicolas','Olivier','Pierre','Raphaël','Romain',
            'Samuel','Simon','Sylvain','Thomas','Théo','Timoté','Yoann',
        ],
        'DEU' => [
            'Alexander','Andreas','Christian','Daniel','David','Erik','Felix','Florian',
            'Hans','Jonas','Julian','Kevin','Klaus','Levin','Lukas','Marco','Markus',
            'Michael','Moritz','Nico','Patrick','Paul','Peter','Philipp','Sandro',
            'Sebastian','Stefan','Thomas','Tim','Tobias',
        ],
        'ENG' => [
            'Aaron','Adam','Ben','Bradley','Charlie','Connor','Daniel','Declan',
            'Edward','Ethan','Freddie','George','Harry','Jack','Jake','James',
            'Jamie','Josh','Lee','Lewis','Marcus','Matthew','Michael','Oliver',
            'Owen','Ryan','Sam','Scott','Thomas','Will',
        ],
        'PRT' => [
            'André','Bruno','Carlos','Cristiano','Diogo','Eduardo','Fábio','Fernando',
            'Francisco','Gonçalo','Hugo','João','Jorge','José','Luís','Manuel',
            'Marco','Nuno','Paulo','Pedro','Rafael','Ricardo','Rúben','Rúi','Sérgio',
            'Tiago','Tomás','Vítor','Xavier','Zé',
        ],
        'NLD' => [
            'Arjen','Bas','Davy','Demy','Dick','Dion','Donny','Eljero','Frenkie',
            'Georginio','Hans','Jaap','Jan','Jeroen','Johan','Klaas','Lars','Luc',
            'Marco','Mark','Memphis','Nigel','Patrick','Paul','Robben','Robin',
            'Stefan','Virgil','Wim','Xavi',
        ],
        'HRV' => [
            'Antonio','Bruno','Danijel','Dejan','Domagoj','Duje','Filip','Goran',
            'Hrvoje','Ivan','Josip','Krešimir','Luka','Marin','Mario','Marko',
            'Mateo','Milan','Nenad','Nikola','Petar','Sandro','Saša','Stjepan',
            'Tomislav','Vedran','Viktor','Vladko','Zvonimir','Žarko',
        ],
    ];

    private array $lastNames = [
        'ESP' => [
            'García','Martínez','López','Sánchez','González','Pérez','Rodríguez',
            'Fernández','Álvarez','Torres','Ramírez','Flores','Moreno','Jiménez',
            'Díaz','Vargas','Castillo','Silva','Herrera','Reyes','Medina','Ortega',
            'Muñoz','Ruiz','Romero','Aguilar','Navarro','Molina','Guerrero','Ramos',
        ],
        'BRA' => [
            'Silva','Santos','Oliveira','Souza','Lima','Costa','Ferreira','Alves',
            'Rodrigues','Nascimento','Carvalho','Martins','Araújo','Melo','Barbosa',
            'Ribeiro','Gomes','Dias','Nunes','Cardoso','Teixeira','Pereira','Mendes',
            'Correia','Ramos','Moreira','Rocha','Pinto','Castro','Freitas',
        ],
        'ARG' => [
            'González','Rodríguez','García','López','Martínez','Pérez','Fernández',
            'Sánchez','Romero','Torres','Díaz','Álvarez','Ruiz','Ramírez','Flores',
            'Moreno','Ortega','Suárez','Castro','Vargas','Medina','Reyes','Herrera',
            'Molina','Blanco','Ramos','Vidal','Gutiérrez','Ibáñez','Méndez',
        ],
        'FRA' => [
            'Martin','Bernard','Dubois','Thomas','Robert','Richard','Petit','Durand',
            'Leroy','Moreau','Simon','Laurent','Lefebvre','Michel','Garcia','David',
            'Bertrand','Roux','Vincent','Fournier','Morel','Girard','André','Mercier',
            'Dupont','Lambert','Bonnet','François','Martinez','Legrand',
        ],
        'DEU' => [
            'Müller','Schmidt','Schneider','Fischer','Weber','Meyer','Wagner','Becker',
            'Schulz','Hoffmann','Schäfer','Koch','Bauer','Richter','Klein','Wolf',
            'Schröder','Neumann','Schwarz','Zimmermann','Braun','Krüger','Hartmann',
            'Lange','Werner','Schmitz','Krause','Meier','Lehmann','Huber',
        ],
        'ENG' => [
            'Smith','Jones','Williams','Taylor','Brown','Davies','Evans','Wilson',
            'Thomas','Roberts','Johnson','Lewis','Walker','Robinson','Thompson',
            'White','Jackson','Wright','Green','Harris','Clarke','Cooper','Ward',
            'Martin','Richardson','Moore','Collins','Edwards','Hughes','Turner',
        ],
        'PRT' => [
            'Silva','Santos','Ferreira','Pereira','Oliveira','Costa','Rodrigues',
            'Martins','Jesus','Sousa','Fernandes','Gonçalves','Gomes','Lopes','Marques',
            'Alves','Almeida','Ribeiro','Pinto','Carvalho','Teixeira','Moreira',
            'Correia','Melo','Nunes','Mendes','Ramos','Cardoso','Dias','Rocha',
        ],
        'NLD' => [
            'de Jong','de Vries','van den Berg','van Dijk','Bakker','Janssen','Visser',
            'Smit','Meijer','de Boer','Mulder','van Leeuwen','Bos','Dekker','Dijkstra',
            'Hendriks','Peters','Vermeulen','van der Berg','Willems','Kok','Hoekstra',
            'van Loon','Brouwer','Wolff','Mol','Bruins','Huisman','van Dam','Vos',
        ],
        'HRV' => [
            'Horvat','Kovačević','Babić','Marić','Tomić','Jurić','Perić','Matić',
            'Blažević','Novak','Knežević','Vuković','Petrović','Dragičević','Bošnjak',
            'Šimić','Filipović','Pavlović','Rukavina','Bušić','Galić','Milić','Vidić',
            'Lučić','Benić','Oreč','Sučić','Grgić','Zorić','Biuk',
        ],
    ];

    public function safeUp(): void
    {
        foreach ($this->firstNames as $nat => $names) {
            foreach ($names as $name) {
                $exists = (int) $this->db->createCommand(
                    'SELECT COUNT(*) FROM {{%name_first}} WHERE name=:n AND nationality=:nat',
                    [':n' => $name, ':nat' => $nat]
                )->queryScalar();
                if ($exists === 0) {
                    $this->insert('{{%name_first}}', ['name' => $name, 'nationality' => $nat, 'gender' => 0]);
                }
            }
        }

        foreach ($this->lastNames as $nat => $names) {
            foreach ($names as $name) {
                $exists = (int) $this->db->createCommand(
                    'SELECT COUNT(*) FROM {{%name_last}} WHERE name=:n AND nationality=:nat',
                    [':n' => $name, ':nat' => $nat]
                )->queryScalar();
                if ($exists === 0) {
                    $this->insert('{{%name_last}}', ['name' => $name, 'nationality' => $nat]);
                }
            }
        }
    }

    public function safeDown(): void
    {
        $nats = array_keys($this->firstNames);
        $in = implode(',', array_fill(0, count($nats), '?'));
        $this->db->createCommand("DELETE FROM {{%name_first}} WHERE nationality IN ($in)", $nats)->execute();
        $this->db->createCommand("DELETE FROM {{%name_last}} WHERE nationality IN ($in)", $nats)->execute();
    }
}
