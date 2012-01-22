<?php

    spl_autoload_register("autoload");

    use Everyman\Neo4j\Client,
    Everyman\Neo4j\Transport,
    Everyman\Neo4j\Traversal,
    Everyman\Neo4j\Node;

    $client = new Client(new Transport('localhost', 7474));

    try
    {
        $traversal = new Traversal($client);
        $traversal->addRelationship("calls")
                ->setPruneEvaluator(Traversal::PruneNone)
                ->setReturnFilter(Traversal::ReturnAllButStart)
                ->setMaxDepth(4);

        $startNode = $client->getNode(1);

        $nodes = $traversal->getResults($startNode, Traversal::ReturnTypeNode);

        foreach($nodes as $node)
            print_r($node->getProperties());

//
    }
    catch (Everyman\Neo4j\Exception $e)
    {
        echo "Something went wrong!\n";
        print_r($e);
    }



?>