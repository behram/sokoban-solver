<?php
ini_set('max_execution_time', 10000);
ini_set('memory_limit', '-1');

include 'vendor/autoload.php';

use Tree\Node\Node;

class Sokoban
{
    const _TREE = 1;
    const _WALL = 2;
    const _BOMB = 3;
    const _EMPTY = 4;
    const _HUMAN = 5;
    const _BOX = 6;
    const _BOX_AND_BOMB = 7;
    const _BOMB_AND_HUMAN = 8;

    private $typeColors = [
        1 => "green",
        2 => "red",
        3 => "blue",
        4 => "white",
        5 => "orange",
        6 => "grey",
        7 => "Aqua",
        8 => "black",
    ];

    /**
     * @var Node|NULL
     */
	public $tree = NULL;

	public $triedMaps = [];

    public $hashMaps = [];

    public $counter = 0;

    /**
     * @var Node
     */
    public $lastNode;

	public function parseMap($map)
	{
		$parsedMap = [];
		$map = explode("\n", $map);
		unset($map[0]);
		unset($map[10]);
		$map = array_values($map);
		foreach ($map as $mapKey => $line) {
			$lineExplosed = explode(" ", $line);
			foreach ($lineExplosed as $key => $value) {
				$parsedMap[$mapKey][$key] = (int)$value;
			}
		}

		return $parsedMap;
	}

	public function findHuman($parsedMap)
	{
		foreach ($parsedMap as $lineKey => $line) {
			foreach ($line as $key => $value) {
				if($value == self::_HUMAN || $value == self::_BOMB_AND_HUMAN){
					return [$lineKey, $key];
				}
			}
		}
	}

	public function findBoxes($parsedMap)
	{
		$boxes = [];
		foreach ($parsedMap as $lineKey => $line) {
			foreach ($line as $key => $value) {
				if($value == 6){
					$boxes[] = [$lineKey, $key];
				}
			}
		}
		return $boxes;
	}

	public function findBoxAndBombs($parsedMap)
	{
		$boxAndBombs = [];
		foreach ($parsedMap as $lineKey => $line) {
			foreach ($line as $key => $value) {
				if($value == 7){
					$boxAndBombs[] = [$lineKey, $key];
				}
			}
		}
		return $boxAndBombs;
	}

	public function isEmpty($parsedMap, $x, $y)
	{
		return $parsedMap[$x][$y] === 4;
	}

	public function isFinished($parsedMap)
	{
		return count($this->findBoxes($parsedMap)) === 0;
	}

	public function exposeMap($parsedMap, $left = 0)
	{
		echo '<table style="border: 1px solid black;margin-left: '.$left.'px">';
		foreach ($parsedMap as $lineKey => $line) {
			echo '<tr style="border: 1px solid black;">';
			foreach ($line as $key => $value) {
				if($value == 4){$value = "&nbsp;";}
				echo '<td style="border: 1px solid black;background-color: '.$this->typeColors[$value].'">&nbsp;'.$value."&nbsp;</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}

	public function mapHash($value)
    {
        return md5(serialize($value));
    }

    /**
     * @param Node $node
     * @return bool
     */
	public function exposeTree(Node $node)
	{
	    $depth = $node->getDepth();
	    $this->exposeMap($node->getValue(), $depth*30);

        foreach ($node->getChildren() as $child){
            $this->exposeTree($child);
        }
	}

    /**
    /**
     * @param Node $node
     * @return bool
     */
    public function exposeTreeFromLast($node, $boxCount = 0)
    {
        if($boxCount !== count($this->findBoxes($node->getValue()))){
            $this->exposeMap($node->getValue(), 0);
            echo "<br>";
        }
        if($node->isRoot()){
            return false;
        }
        $this->exposeTreeFromLast($node->getParent(), count($this->findBoxes($node->getValue())));
    }

	public function move(Node $parentNode, $parsedMap)
	{
	    $parsedMapHash = md5(serialize($parsedMap));
		if(in_array($parsedMapHash, $this->triedMaps)){
			return false;
		}
		$childNode = new Node($parsedMap);
		//$parentNode->addChild($childNode);

		if($this->isFinished($parsedMap)){
		    $this->lastNode = $childNode;
		    $this->exposeMap($parsedMap);
		    exit("hello");

			return true;
		}
		$this->triedMaps[] = $parsedMapHash;
		$this->counter = $this->counter+1;
		echo $this->counter."<br>";

		$this->moveDirections($childNode);

		return $parentNode;
	}

	public function moveDirections(Node $childNode)
    {
        $parsedMap = $childNode->getValue();
        $human = $this->findHuman($parsedMap);

        $boxes = $this->findBoxes($parsedMap);
        foreach ($boxes as $box){
            $upVal = $parsedMap[$box[0]-1][$box[1]];
            $leftVal = $parsedMap[$box[0]][$box[1]-1];
            $downVal = $parsedMap[$box[0]+1][$box[1]];
            $rightVal = $parsedMap[$box[0]][$box[1]+1];

            $upLeftCornerVal = $parsedMap[$box[0]-1][$box[1]-1];
            $upRightCornerVal = $parsedMap[$box[0]-1][$box[1]+1];
            $downLeftCornerVal = $parsedMap[$box[0]+1][$box[1]-1];
            $downRightCornerVal = $parsedMap[$box[0]+1][$box[1]+1];
            // if up and left is wall
            if($upVal == self::_WALL && $leftVal == self::_WALL){
                return false;
            }
            // if up and right is wall
            if($upVal == self::_WALL && $rightVal == self::_WALL){
                return false;
            }
            // if down and left is wall
            if($downVal == self::_WALL && $leftVal == self::_WALL){
                return false;
            }
            // if down and right is wall
            if($downVal == self::_WALL && $rightVal == self::_WALL){
                return false;
            }

            //if up,left and left up corner is box
            if($upVal == self::_BOX && $leftVal == self::_BOX && $upLeftCornerVal == self::_BOX){
                return false;
            }
            //if up,right and right up corner is box
            if($upVal == self::_BOX && $rightVal == self::_BOX && $upRightCornerVal == self::_BOX){
                return false;
            }
            //if down,left and left down corner is box
            if($downVal == self::_BOX && $leftVal == self::_BOX && $downLeftCornerVal == self::_BOX){
                return false;
            }
            //if down,right and right down corner is box
            if($downVal == self::_BOX && $rightVal == self::_BOX && $downRightCornerVal == self::_BOX){
                return false;
            }
        }

        #up
        $oneNext = [$human[0]-1, $human[1]];
        $twoNext = [$human[0]-2, $human[1]];
        $threeNext = [$human[0]-3, $human[1]];
        $moveUp = $this->moveToPointer($parsedMap, $oneNext, $twoNext, $threeNext);
        if($moveUp !== false){
            $this->move($childNode, $moveUp);
        }
        #down
        $oneNext = [$human[0]+1, $human[1]];
        $twoNext = [$human[0]+2, $human[1]];
        $threeNext = [$human[0]+3, $human[1]];
        $moveDown = $this->moveToPointer($parsedMap, $oneNext, $twoNext, $threeNext);
        if($moveDown !== false){
            $this->move($childNode, $moveDown);
        }
        #left
        $oneNext = [$human[0], $human[1]-1];
        $twoNext = [$human[0], $human[1]-2];
        $threeNext = [$human[0], $human[1]-3];
        $moveLeft = $this->moveToPointer($parsedMap, $oneNext, $twoNext, $threeNext);
        if($moveLeft !== false){
            $this->move($childNode, $moveLeft);
        }
        #right
        $oneNext = [$human[0], $human[1]+1];
        $twoNext = [$human[0], $human[1]+2];
        $threeNext = [$human[0], $human[1]+3];
        $moveRight = $this->moveToPointer($parsedMap, $oneNext, $twoNext, $threeNext);
        if($moveRight !== false){
            $this->move($childNode, $moveRight);
        }
    }

    public function moveToPointer($parsedMap, $oneNext, $twoNext, $threeNext)
    {
        $human = $this->findHuman($parsedMap);
        $humanValue = $parsedMap[$human[0]][$human[1]];

        $oneNextValue = $parsedMap[$oneNext[0]][$oneNext[1]];
        $twoNextValue = $parsedMap[$twoNext[0]][$twoNext[1]];

        if($oneNextValue == self::_WALL){
            return false;
        }
        if($oneNextValue == self::_BOX && $twoNextValue == self::_BOX){
            return false;
        }
        if($oneNextValue == self::_BOX && $twoNextValue == self::_WALL){
            return false;
        }

        //if one next is empty
        if($oneNextValue == self::_EMPTY){
            if($humanValue == self::_BOMB_AND_HUMAN){
                //make bomb
                $parsedMap[$human[0]][$human[1]] = self::_BOMB;
            }else{
                //make empty
                $parsedMap[$human[0]][$human[1]] = self::_EMPTY;
            }
            //move human
            $parsedMap[$oneNext[0]][$oneNext[1]] = self::_HUMAN;

            return $parsedMap;
        }

        // if one next is bomb
        if($oneNextValue == self::_BOMB){
            if($humanValue == self::_BOMB_AND_HUMAN){
                //make bomb
                $parsedMap[$human[0]][$human[1]] = self::_BOMB;
            }else{
                //make empty
                $parsedMap[$human[0]][$human[1]] = self::_EMPTY;
            }
            //move human
            $parsedMap[$oneNext[0]][$oneNext[1]] = self::_BOMB_AND_HUMAN;

            return $parsedMap;
        }

        //if one next is box and two next is empty
        if($oneNextValue == self::_BOX && $twoNextValue == self::_EMPTY){
            if($humanValue == self::_BOMB_AND_HUMAN){
                //make bomb
                $parsedMap[$human[0]][$human[1]] = self::_BOMB;
            }else{
                //make empty
                $parsedMap[$human[0]][$human[1]] = self::_EMPTY;
            }
            //move human
            $parsedMap[$oneNext[0]][$oneNext[1]] = self::_HUMAN;
            //move box
            $parsedMap[$twoNext[0]][$twoNext[1]] = self::_BOX;

            return $parsedMap;
        }

        //if one next is box and two next is bomb
        if($oneNextValue == self::_BOX && $twoNextValue == self::_BOMB){
            if($humanValue == self::_BOMB_AND_HUMAN){
                //make bomb
                $parsedMap[$human[0]][$human[1]] = self::_BOMB;
            }else{
                //make empty
                $parsedMap[$human[0]][$human[1]] = self::_EMPTY;
            }
            //move human
            $parsedMap[$oneNext[0]][$oneNext[1]] = self::_HUMAN;
            //move box
            $parsedMap[$twoNext[0]][$twoNext[1]] = self::_BOX_AND_BOMB;

            return $parsedMap;
        }

        // if one next box and bomb and two next is bomb
        if($oneNextValue == self::_BOX_AND_BOMB && $twoNextValue == self::_BOMB){
            if($humanValue == self::_BOMB_AND_HUMAN){
                //make bomb
                $parsedMap[$human[0]][$human[1]] = self::_BOMB;
            }else{
                //make empty
                $parsedMap[$human[0]][$human[1]] = self::_EMPTY;
            }
            //move human
            $parsedMap[$oneNext[0]][$oneNext[1]] = self::_BOMB_AND_HUMAN;
            //move box
            $parsedMap[$twoNext[0]][$twoNext[1]] = self::_BOX_AND_BOMB;

            return $parsedMap;
        }

        // if one next box and bomb and two next is empty
        if($oneNextValue == self::_BOX_AND_BOMB && $twoNextValue == self::_EMPTY){
            if($humanValue == self::_BOMB_AND_HUMAN){
                //make bomb
                $parsedMap[$human[0]][$human[1]] = self::_BOMB;
            }else{
                //make empty
                $parsedMap[$human[0]][$human[1]] = self::_EMPTY;
            }
            //move human
            $parsedMap[$oneNext[0]][$oneNext[1]] = self::_BOMB_AND_HUMAN;
            //move box
            $parsedMap[$twoNext[0]][$twoNext[1]] = self::_BOX;

            return $parsedMap;
        }

        return false;
    }

	public function valueIsEqual(Node $node, $parentValue)
    {
        return $node->getValue() == $parentValue;
    }
}
$types = [
    1 => "TREE",
    2 => "WALL",
    3 => "BOMB",
    4 => "EMPTY",
    5 => "HUMAN",
    6 => "BOX",
    7 => "BOX_AND_BOMB",
];

$map10 = "
2 2 2 2 2 2 2 2 1
2 4 4 2 4 4 4 2 1
2 4 6 3 3 6 4 2 1
2 5 6 3 7 4 2 2 1
2 4 6 3 3 6 4 2 1
2 4 4 2 4 4 4 2 1
2 2 2 2 2 2 2 2 1
1 1 1 1 1 1 1 1 1
1 1 1 1 1 1 1 1 1
";

$sokoban = new Sokoban();
$parsedMap = $sokoban->parseMap($map10);
$sokoban->tree = new Node($parsedMap);
$sokoban->tree = $sokoban->move($sokoban->tree, $parsedMap);
$sokoban->exposeMap($parsedMap);
//$sokoban->exposeTree($sokoban->tree);
//var_dump($sokoban->tree->getHeight());
//$sokoban->exposeTreeFromLast($sokoban->lastNode);

