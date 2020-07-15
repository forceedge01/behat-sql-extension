.PHONY: test
test:
	docker-compose up

pg-db:
	docker run --name my-postgres -e POSTGRES_PASSWORD=password -p5432:5432 -d postgres