# First stage (build)
FROM microsoft/dotnet:2.1-sdk AS build
WORKDIR /app

# Copy solution as distinct layer
COPY *.sln .
COPY TileServer/*.csproj ./TileServer/
RUN dotnet restore

# Copy everything else and build
COPY TileServer/. ./TileServer/
WORKDIR /app/TileServer
RUN dotnet publish -c Release -o out

# Second stage (execution)
FROM microsoft/dotnet:2.1-aspnetcore-runtime AS runtime
WORKDIR /app
COPY --from=build /app/TileServer/out ./

ENV ASPNETCORE_URLS http://+:8000
ENV ASPNETCORE_ENVIRONMENT Development

EXPOSE 8000

USER 1000

ENTRYPOINT ["dotnet", "TileServer.dll"]
