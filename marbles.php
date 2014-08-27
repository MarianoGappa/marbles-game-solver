<?php
Controller::execute();

/**
 * MARBLES GAME SOLVER
 * (31/07/2011)
 *
 * @author Mariano Lopez Gappa

 * ALGORITHM:
 * - This script employs a depth-first tree search algorithm combined with 
 * blacklisting of nodes, using a CRC32 hash function for array indexation.
 * - It's been profiled to find a solution in less than one second for the
 * default starting Map set on an 2.8Ghz clock speed thread.
 * - As it uses a depth-first tree search algorithm, it obviously returns ONLY
 * THE FIRST SOLUTION it finds.
 *
 * BONUS FEATURES:
 * - The default Map can easily be modified.
 * - Maps can also have arbitrary size, and the matrix doesn't need to be NxN.
 * - It is quite simple to add diagonal movements. Simply add the movements to
 * Marble->getAllMoves(). Diagonal movements will have, for example: (1, -1) deltas.
 * - You can more or less gauge processor/memory balance by adjusting the
 * Map->calculateHash() settings. More info available on its PHPDoc.
 */


/**
 * Controls application flow
 */
class Controller {
  // ---------------------------------------------------------------------
  /**
   * Main method
   */
  public static function execute() {
    /* Show HTML headers and main screen */
    GUIHandler::showHeader();

    /* Attempt to solve the Marbles game, and show results */
    $moves = self::solveMarbles();

    /* Close HTML tags */
    GUIHandler::showFooter();
  }

  // ---------------------------------------------------------------------
  /* Attempt to solve the game and show results */
  private static function solveMarbles() {
    $marblesSolver = new MarblesSolver(new DefaultMap());

    /* Profile solution */
    $start  = microtime(true);
    $moves  = $marblesSolver->solve();
    $end    = microtime(true);

    $map = $marblesSolver->getInitialMap();

    /* Show results */
    if(!$moves)
      GUIHandler::showError();
    else
      GUIHandler::showSolution($map, $moves, $start, $end);

    return $moves;
  }
}

/**
 * Handles all user interface tasks
 */
class GUIHandler {
  // ---------------------------------------------------------------------
  public static function showHeader() {
    echo "
      <html>
      <head>
      <style>
      *{font-family: 'Courier New'; font-size: 12;}
      </style>
      <body>
    ";
  }

  // ---------------------------------------------------------------------
  public static function showFooter() {
    echo "
      </body>
      </html>
    ";
  }

  // ---------------------------------------------------------------------
  public static function showError() {
    echo "No solution was found :(";
  }

  // ---------------------------------------------------------------------
  public static function showSolution(Map $map, $moves, $start, $end) {
    $map->show();
    foreach($moves as $move) {
      $map = $map->executeMove($move);
      $map->show();
    }
    echo "<div style='clear:both'></div>";
    echo "<br/>Solved in " . ((float)$end-(float)$start) . " seconds.<br/><br/>";
  }
}

/**
 * This class solves the Marbles game.
 */
class MarblesSolver {

  /**
   * "Black list" for Maps which don't lead to a solution
   * @var array
   */
  private $deadEndMaps = array();

  /**
   * Initial Map used for the algorithm
   * @var Map
   */
  private $initialMap;

  // ---------------------------------------------------------------------
  public function __construct($map) {
    $this->initialMap = $map;
  }

  // ---------------------------------------------------------------------
  /**
   * The static Controller will invoke this method to start the algorithm.
   *
   * @return bool/array
   */
  public function solve() {
    return $this->solveMap($this->initialMap);
  }

  // ---------------------------------------------------------------------
  /**
   * The main algoritm
   *
   * @param Map $map
   * @param array $moves
   * @return boolean/array
   */
  private function solveMap(Map $map, $moves = array()) {
    /* Check if the current Map is a solution */
    if($map->getMarbleAmount() == 1)
      return $moves;

    /* For each possible movement from the current Map */
    foreach($map->getAvailableMoves() as $move) {
      /* Execute the movement */
      $newMap = $map->executeMove($move);

      /* If the new Map is not in the "black list" */
      if(!$this->isDeadEndMap($newMap)) {
        /* Append the movement to the movement stack */
        array_push($moves, $move);

        /* Try to solve from this new Map. If successful, return the movement stack */
        if($result = $this->solveMap($newMap, $moves))
          return $result;

        /* Abandon the movement, since it didn't lead to the solution */
        array_pop($moves);
      }
    }

    /* Append the dead-end Map to the "black list" */
    $this->deadEndMaps[$map->getHash()][] = $map;

    return false;
  }

  // ---------------------------------------------------------------------
  /**
   * Determines if a Map doesn't lead to a solution. It does this by looking
   * for the Map in the deadEndMap array. This array is indexed by Map hashes.
   *
   * @param Map $map
   * @return boolean
   */
  private function isDeadEndMap($map) {
    if(!isset($this->deadEndMaps[$map->getHash()]))
      return false;

    foreach($this->deadEndMaps[$map->getHash()] as $deadEndMap) {
      if($map == $deadEndMap)
        return true;
    }

    return false;
  }

  // ---------------------------------------------------------------------
  public function getInitialMap() {
    return $this->initialMap;
  }
}


/**
 * Represents a Marble's movement.
 */
class Move {
  /**
   * The Marble's initial coordinates
   * @var Coordinates
   */
  private $position;

  /**
   * The Marble's movement direction, in two-dimensional delta format
   * @var Coordinates
   */
  private $delta;

  // ---------------------------------------------------------------------
  public function __construct(Coordinates $position, Coordinates $delta) {
    $this->position = $position;
    $this->delta = $delta;
  }

  // ---------------------------------------------------------------------
  public function getPosition() {
    return $this->position;
  }

  // ---------------------------------------------------------------------
  public function getDelta() {
    return $this->delta;
  }
}

/**
 * The Coordinates class is used both for representing a Marble's coordinates
 * and its movement's direction.
 */
class Coordinates {
  private $x;
  private $y;

  // ---------------------------------------------------------------------
  public function __construct($x, $y) {
    $this->x = $x;
    $this->y = $y;
  }

  // ---------------------------------------------------------------------
  public function getX() {
    return $this->x;
  }

  // ---------------------------------------------------------------------
  public function getY() {
    return $this->y;
  }
}

/**
 * A Marble object represents a Marble's coordinates on a Map.
 */
class Marble extends Coordinates {

  // ---------------------------------------------------------------------
  /**
   * Returns all possible movements for the current Marble object.
   * If one was to include diagonal movements, the appropriate Move instances
   * should be added to the returned array.
   * 
   * @return array
   */
  public function getAllMoves() {
    return array(
      new Move($this, new Coordinates(-1, 0)), /* Left */
      new Move($this, new Coordinates(0, -1)), /* Up */
      new Move($this, new Coordinates(1, 0)),  /* Right */
      new Move($this, new Coordinates(0, 1))   /* Down */
    );
  }
}


/**
 * Instances of the Map class are static representations of board sets
 */
class Map {
  const UNALLOWED  = '.';
  const MARBLE     = 'X';
  const DEPRESSION = 'O';

  /**
   * The Map's cell set
   * @var array
   */
  private $cells;

  /**
   * The amount of MARBLE characters on the Map's cell set
   * @var int
   */
  private $marbleAmount;
  
  /**
   * A 4-character set hash for Map indexation
   * @var string
   */
  private $hash;

  // ---------------------------------------------------------------------
  /**
   * Instantiates the Map class. The marbleAmount and hash parameters should
   * only be omitted for the initial Map, because Maps are massively created
   * within the "solve" recursive function, and innecessary calculations will
   * penalize the global algorithm's performance.
   *
   * @param array $cells
   * @param int $marbleAmount
   * @param string $hash
   */
  public function __construct($cells, $marbleAmount = null, $hash = null) {
    $this->cells        = $cells;

    if($marbleAmount === null)
      $this->marbleAmount = $this->calculateMarbleAmount();
    else
      $this->marbleAmount = $marbleAmount;

    if($hash === null)
      $this->hash = $this->calculateHash();
    else
      $this->hash = $hash;
  }

  // ---------------------------------------------------------------------
  /**
   * Returns all possible Moves from all Marbles from the current Map. This
   * method is likely to be the most time-consuming in the whole algorithm.
   *
   * @return array
   */
  public function getAvailableMoves() {
    $availableMoves = array();
    foreach($this->getMarbles() as $marble) {
      foreach($marble->getAllMoves() as $move) {
        if($this->isMovePossible($move))
          $availableMoves[] = $move;
      }
    }

    return $availableMoves;
  }

  // ---------------------------------------------------------------------
  /**
   * Executes a given Move for the current Map, by cloning the Map object and
   * making the modifications on the cloned Map. The hash needs to be recalculated
   * for the modified Map. The marbleAmount on the cloned Map will always be the
   * original amount minus one.
   *
   * @param Move $move
   * @return Map
   */
  public function executeMove(Move $move) {
    $newMap = new Map($this->cells, $this->marbleAmount - 1, 0);

    /* Set a DEPRESSION character on the initial movement coordinates */
    $newMap->modifyMap($move->getPosition()->getX(), $move->getPosition()->getY(), self::DEPRESSION);
    /* Set a DEPRESSION character on the cell inbetween initial and final movement coordinates */
    $newMap->modifyMap($move->getPosition()->getX() + $move->getDelta()->getX(), $move->getPosition()->getY() + $move->getDelta()->getY(), self::DEPRESSION);
    /* Set a MARBLE character on the final movement coordinates */
    $newMap->modifyMap($move->getPosition()->getX() + $move->getDelta()->getX() * 2, $move->getPosition()->getY() + $move->getDelta()->getY() * 2, self::MARBLE);

    $newMap->setHash($newMap->calculateHash());

    return $newMap;
  }

  // ---------------------------------------------------------------------
  /**
   * Verifies if a movement is possible for the current Map. This method performs
   * a series of optional conditions, like verifying that the initial coordinates
   * don't exceed the map and host a MARBLE character, which should't be necessary
   * and slightly decreases the global algorithm's performance.
   *
   * @param Move $move
   * @return boolean
   */
  public function isMovePossible(Move $move) {
    /* Does the cell exist? */
    if(isset($this->cells[$move->getPosition()->getY()][$move->getPosition()->getX()])) {
      /* Does it host a MARBLE character? */
      if($this->cells[$move->getPosition()->getY()][$move->getPosition()->getX()] == self::MARBLE) {
        /* Do the MARBLE destination coordinates exist? */
        if(isset($this->cells[$move->getPosition()->getY() + $move->getDelta()->getY() * 2][$move->getPosition()->getX() + $move->getDelta()->getX() * 2])) {
          /* Is the destination cell a DEPRESSION character? */
          if($this->cells[$move->getPosition()->getY() + $move->getDelta()->getY() * 2][$move->getPosition()->getX() + $move->getDelta()->getX() * 2] == self::DEPRESSION) {
            /* Is the middle cell inbetween the initial and final cells a MARBLE character? */
            if($this->cells[$move->getPosition()->getY() + $move->getDelta()->getY()][$move->getPosition()->getX() + $move->getDelta()->getX()] == self::MARBLE) {
              return true;
            }
          }
        }
      }
    }
    return false;
  }

  // ---------------------------------------------------------------------
  /**
   * Modifies a single cell value. Maps should only be modified when a movement
   * is executed.
   *
   * @param int $x
   * @param int $y
   * @param string $value
   */
  public function modifyMap($x, $y, $value) {
    $this->cells[$y][$x] = $value;
  }

  // ---------------------------------------------------------------------
  /**
   * Sweeps the cells looking for MARBLE characters. When a MARBLE character is
   * found, a Marble (Coordinate) object is instantiated with the appropriate
   * coordinates. The Marble object collection is ultimately returned.
   * 
   * @return Marble 
   */
  public function getMarbles() {
    $marbles = array();
    foreach($this->cells as $y => $line) {
      foreach($line as $x => $cell) {
        if($cell == self::MARBLE)
          $marbles[] = new Marble($x, $y);
      }
    }

    return $marbles;
  }

  // ---------------------------------------------------------------------
  /**
   * @return int
   */
  public function getMarbleAmount() {
    return $this->marbleAmount;
  }

  // ---------------------------------------------------------------------
  /**
   * Sweeps the cells looking for MARBLE characters. This calculation should
   * only be performed on initial map. Further maps should be descending from
   * the initial map, in which case decreasing the marble amount by one should
   * suffice.
   *
   * @return int 
   */
  public function calculateMarbleAmount() {
    $marbleAmount = 0;
    foreach($this->cells as $line) {
      foreach($line as $cell) {
        if($cell == self::MARBLE)
          $marbleAmount++;
      }
    }

    return $marbleAmount;
  }

  // ---------------------------------------------------------------------
  /**
   * Map hashes are used for indexation within the "deadEndMaps" array.
   * Variations of this method provide interesting results:
   * - Using md5 instead of crc32 appears to achieve less collision, but
   * results are not numeric. Tests show similar performance.
   * - Using 4-character length numeric hashes allow up to 10000 distinct hash
   * values, which is great for default map settings but scary for memory
   * consumption. Fewer characters are better for memory and worse for
   * processing. 4 appears to be a sweet spot.
   *
   * @return string
   */
  public function calculateHash() {
    return substr(crc32(serialize($this->cells)), -4);
  }

  // ---------------------------------------------------------------------
  /**
   * @return string
   */
  public function getHash() {
    return $this->hash;
  }

  // ---------------------------------------------------------------------
  /**
   * Hash should only be set through this method when modifying an instance.
   * @param int $hash
   */
  public function setHash($hash) {
    $this->hash = $hash;
  }

  // ---------------------------------------------------------------------
  /**
   * Only used through GUIHandler. Displays the current map.
   */
  public function show() {
    echo "<div style='letter-spacing: 0.6em; float: left; width: 0 auto; padding: 3px; margin: 3px; border: 1px solid grey;'>";

    $linesString = array();
    foreach($this->cells as $line) {
      $lineString = '';
      foreach($line as $cell)
        $lineString .= $cell;

      $linesString[] = $lineString;
    }

    echo implode('<br/>', $linesString);
    echo "</div>";
  }

}

/**
 * When instantiating a DefaultMap instead of a Map, the default cell set is
 * implicitly provided.
 */
class DefaultMap extends Map {

  // ---------------------------------------------------------------------
  public function __construct() {
    parent::__construct(self::defaultCells());
  }

  // ---------------------------------------------------------------------
  /**
   * @return array
   */
  public static function defaultCells() {
    return array(
      array('.', '.', 'X', 'X', 'X', '.', '.'),
      array('.', '.', 'X', 'X', 'X', '.', '.'),
      array('X', 'X', 'X', 'X', 'X', 'X', 'X'),
      array('X', 'X', 'X', 'O', 'X', 'X', 'X'),
      array('X', 'X', 'X', 'X', 'X', 'X', 'X'),
      array('.', '.', 'X', 'X', 'X', '.', '.'),
      array('.', '.', 'X', 'X', 'X', '.', '.'),
    );
  }
}
