<?php

    use Everyman\Neo4j\Client,
        Everyman\Neo4j\Transport,
        Everyman\Neo4j\Exception,
        Everyman\Neo4j\Index\NodeIndex,
        Everyman\Neo4j\Cypher\Query,
        Everyman\Neo4j\Query\ResultSet;

    class CallCentreManager
    {
        private $client;

        public function __construct()
        {
            try
            {
                $this->client = new Client(new Transport('localhost', 7474));
            }
            catch(Exception $e)
            {
                print_r($e);
            }
        }

        /**
         * @route               /store/create-sample-data(/:iterations)
         * @routeMethods        GET
         *
         * @param $iterations
         */
        public function createSampleData($iterations)
        {
            $userIndex = new NodeIndex($this->client, "users");
            $callCentreIndex = new NodeIndex($this->client, "call centres");
            $agentIndex = new NodeIndex($this->client, "agents");
            $productIndex = new NodeIndex($this->client, "products");

            for($x = 0; $x < (int) $iterations; $x++)
            {
                $user = $this->client->makeNode()->save();

                $callCentre = $this->client->makeNode()->save();

                $user->setProperty("name", "Danny Kopping $x")
                    ->setProperty("last call", time())
                    ->save();

                $userIndex->add($user, "name", $user->getProperty("name"));
                $userIndex->add($user, "id", $x);

                $user->relateTo($callCentre, "calls")->save();



                $callCentre->setProperty("name", "ACME Call Centre $x")
                    ->save();

                $callCentreIndex->add($callCentre, "name", $callCentre->getProperty("name"));
                $callCentreIndex->add($callCentre, "id", $x);



                $agentA = $this->client->makeNode()->save();
                $agentA->setProperty("name", "Agent A$x")
                    ->save()
                    ->relateTo($callCentre, "works for")
                    ->save();

                $agentB = $this->client->makeNode()->save();
                $agentB->setProperty("name", "Agent B$x")
                    ->save()
                    ->relateTo($callCentre, "works for")
                    ->save();

                $agentC = $this->client->makeNode()->save();
                $agentC->setProperty("name", "Agent C$x")
                    ->save()
                    ->relateTo($callCentre, "works for")
                    ->save();

                $agentC->relateTo($user, "assists")->save();

                $agentIndex->add($agentA, "name", $agentA->getProperty("name"));
                $agentIndex->add($agentA, "id", $agentA->getId());

                $agentIndex->add($agentB, "name", $agentB->getProperty("name"));
                $agentIndex->add($agentB, "id", $agentB->getId());

                $agentIndex->add($agentC, "name", $agentC->getProperty("name"));
                $agentIndex->add($agentC, "id", $agentC->getId());


                $productA = $this->client->makeNode()->save();
                $productA->setProperty("name", "Product A$x")->save();

                $productA->relateTo($agentC, "recommended by")->save();
                $productA->relateTo($callCentre, "offered by")->save();
                $productA->relateTo($user, "recommended to")->save();

                $productIndex->add($productA, "name", $productA->getProperty("name"));
                $productIndex->add($productA, "id", $x);
            }
        }

        /**
         * @route               /user/get/:id
         * @routeMethods        GET
         *
         * @param $id
         * @return ResultSet
         */
        public function getUser($id)
        {
            $query = new Query($this->client, "START n=node:users(id = '$id') RETURN n");
            $results = $query->getResultSet();

            if(empty($results))
                return null;

            foreach($results as $result)
            {
                if(empty($result))
                    continue;

                foreach($result as $key => $value)
                {
                    return $value->getProperties();
                }
            }
        }

    }
?>