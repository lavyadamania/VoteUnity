param(
    [Parameter(Mandatory = $true)]
    [string]$DockerHubUser,

    [string]$Tag = "latest",

    [int]$PushRetries = 3
)

$ErrorActionPreference = "Stop"

$imageLocal = "voteunity-app:$Tag"
$imageRemote = "$DockerHubUser/voteunity-app:$Tag"

Write-Host "Building image: $imageLocal"
docker build -t $imageLocal .
if ($LASTEXITCODE -ne 0) {
    throw "Docker build failed for $imageLocal"
}

Write-Host "Tagging image: $imageRemote"
docker tag $imageLocal $imageRemote
if ($LASTEXITCODE -ne 0) {
    throw "Docker tag failed for $imageRemote"
}

Write-Host "Pushing image to Docker Hub"
$pushSucceeded = $false
for ($attempt = 1; $attempt -le $PushRetries; $attempt++) {
    Write-Host "Push attempt $attempt/$PushRetries"
    docker push $imageRemote
    if ($LASTEXITCODE -eq 0) {
        $pushSucceeded = $true
        break
    }

    if ($attempt -lt $PushRetries) {
        Write-Host "Push failed. Retrying in 5 seconds..."
        Start-Sleep -Seconds 5
    }
}

if (-not $pushSucceeded) {
    throw "Docker push failed after $PushRetries attempts for $imageRemote"
}

Write-Host "Done. Pushed: $imageRemote"
