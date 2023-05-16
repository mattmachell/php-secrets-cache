# Set secrets in Secrets Manager
awslocal secretsmanager create-secret --name local/test-secret \
    --description "Fake test secret for local integration tests" \
    --secret-string "Th15IsAF4kET3sTs3Cr3t!"