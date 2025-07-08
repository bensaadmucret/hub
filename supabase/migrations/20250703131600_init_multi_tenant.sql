-- Migration: Initial multi-tenant setup
-- Created at: 2025-07-03 13:16:00

BEGIN;

-- Enable advanced extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Create organizations table
CREATE TABLE organizations (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name TEXT NOT NULL,
  slug TEXT UNIQUE NOT NULL,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create organization members table
CREATE TABLE organization_members (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  role TEXT NOT NULL CHECK (role IN ('member', 'admin')),
  created_at TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE (organization_id, user_id)
);

-- Optimize for search
CREATE INDEX idx_organizations_name_trigram ON organizations USING gin(name gin_trgm_ops);
CREATE INDEX idx_organizations_slug ON organizations(slug);

-- Row Level Security policies
ALTER TABLE organizations ENABLE ROW LEVEL SECURITY;
ALTER TABLE organization_members ENABLE ROW LEVEL SECURITY;

-- Organizations RLS
CREATE POLICY "Users can see their organizations" 
ON organizations
FOR SELECT
USING (
  EXISTS (
    SELECT 1 FROM organization_members 
    WHERE organization_members.organization_id = organizations.id
    AND organization_members.user_id = auth.uid()
  )
);

-- Organization members RLS
CREATE POLICY "Members can see organization members" 
ON organization_members
FOR SELECT
USING (
  EXISTS (
    SELECT 1 FROM organization_members om
    WHERE om.organization_id = organization_members.organization_id
    AND om.user_id = auth.uid()
  )
);

-- Helper function for JWT claims
CREATE OR REPLACE FUNCTION get_organization_claims()
RETURNS JSONB AS $$
DECLARE
  claims JSONB;
BEGIN
  SELECT jsonb_build_object(
    'organizations', jsonb_agg(
      jsonb_build_object(
        'id', om.organization_id,
        'role', om.role
      )
    )
  )
  INTO claims
  FROM organization_members om
  WHERE om.user_id = auth.uid();

  RETURN claims;
END;
$$ LANGUAGE plpgsql STABLE SECURITY DEFINER;

COMMIT;
