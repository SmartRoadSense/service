# Architecture

```graphviz
digraph hierarchy {
    nodesep=1.0 // increases the separation between nodes

    node [color=Black,shape=box] //All nodes will this shape and colour
    edge [color=Blue,style=dashed] //All the lines look like this
    
    AggDB [shape=cylinder]
    RawDB [shape=cylinder]
    AggregationService [shape=component]

    Web->{UI, Tiles, UploadAPI, BoundingBoxAPI}
    Web->WebhookMonitor
    Tiles->WebService
    BoundingBoxAPI->AggDB
    AggregationService->{AggDB}
    WebService->AggDB
    UploadAPI->RawDB
    RawDB->AggregationService
    
    {rank=same; UI Tiles UploadAPI BoundingBoxAPI WebhookMonitor}
    {rank=same; WebService AggregationService}
    {rank=same; RawDB AggDB}
}

[See online](https://hackmd.io/s/HJF43sgFN#).
