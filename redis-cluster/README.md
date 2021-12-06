# Redis 集群处理

3个 `master` 、每一个下面挂一个 `slave` 节点， 总共6个 Redis 节点

## 启动命令

```
redis-cli --cluster create --cluster-replicas 1 
```