import {
  List,
  Datagrid,
  TextField,
  EmailField,
  Edit,
  SimpleForm,
  TextInput,
  Create,
  Show,
  SimpleShowLayout,
  ArrayField,
  ChipField,
  SingleFieldList,
  DateField,
} from 'react-admin';

export const UserList = () => (
  <List>
    <Datagrid rowClick="edit">
      <TextField source="id" />
      <TextField source="name" />
      <EmailField source="email" />
      <ArrayField source="roles">
        <SingleFieldList>
          <ChipField source="" />
        </SingleFieldList>
      </ArrayField>
      <DateField source="createdAt" />
    </Datagrid>
  </List>
);

export const UserEdit = () => (
  <Edit>
    <SimpleForm>
      <TextInput source="name" required />
      <TextInput source="email" type="email" required />
      <TextInput source="roles" helperText="Comma-separated roles" />
    </SimpleForm>
  </Edit>
);

export const UserCreate = () => (
  <Create>
    <SimpleForm>
      <TextInput source="name" required />
      <TextInput source="email" type="email" required />
      <TextInput source="roles" helperText="Comma-separated roles" defaultValue="ROLE_USER" />
    </SimpleForm>
  </Create>
);

export const UserShow = () => (
  <Show>
    <SimpleShowLayout>
      <TextField source="id" />
      <TextField source="name" />
      <EmailField source="email" />
      <ArrayField source="roles">
        <SingleFieldList>
          <ChipField source="" />
        </SingleFieldList>
      </ArrayField>
      <DateField source="createdAt" />
    </SimpleShowLayout>
  </Show>
);