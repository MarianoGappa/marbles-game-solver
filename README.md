marbles-game-solver
===================

A PHP script to solve the famous marbles game in less than half a second.

![Screenshot of solved Marbles](http://i.imgur.com/ZSpJzMl.png)

**Algorithm**
- This script employs a depth-first tree search algorithm combined with  blacklisting of nodes, using a CRC32 hash function for array indexing.
- It's been profiled to find a solution in less than one second for the default starting Map set on an 2.8Ghz clock speed thread.
- As it uses a depth-first tree search algorithm, it obviously returns ONLY THE FIRST SOLUTION it finds.
 
**Bonus Features**
- The default Map can easily be modified.
- Maps can also have arbitrary size, and the matrix doesn't need to be NxN.
- It is quite simple to add diagonal movements. Simply add the movements to Marble->getAllMoves(). Diagonal movements will have, for example: (1, -1) deltas.
- You can more or less gauge processor/memory balance by adjusting the Map->calculateHash() settings. More info available on its PHPDoc.
